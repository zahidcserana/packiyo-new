<?php

namespace App\Components\BillingRates\StorageByLocationRate;

use App\Exceptions\BillingException;
use App\Models\BillingRate;
use App\Models\Customer;
use App\Models\InventoryLog;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Location;
use App\Models\Product;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MySqlLocationsUsageBillingCalculator implements LocationsUsageBillingCalculator
{
    const SETTING_NO_LOCATION_TYPE = 'no_location';
    public static string $rateType = 'storage_by_location';

    /**
     * @throws BillingException
     */
    public function calculate(BillingRate $rate, Invoice $invoice): void
    {
        $appliesToNoLocationType = Arr::get($rate->settings, self::SETTING_NO_LOCATION_TYPE, false);
        $dateStart = $this->getDateStart($rate, $invoice);
        $locationTypes = json_decode($rate->settings['location_types']);
        $threepl = Customer::find($invoice->customer->parent_id);

        $calculateByPeriod = function ($locations) use ($invoice, $rate, $dateStart) {
            if ($rate->settings['period'] === 'day') {
                $this->calculateDaily($rate, $invoice, $locations);
            } else {
                if ($rate->settings['period'] === 'week') {
                    $dateLocationArray = $this->getLocationsPerDay($invoice, $dateStart, $locations);
                    $this->calculateWeekly($rate, $invoice, $dateLocationArray);
                }

                if ($rate->settings['period'] === 'month') {
                    $this->calculateMonthly($rate, $invoice, $dateStart, $locations);
                }
            }
        };

        if (empty($locationTypes) && !$appliesToNoLocationType) {
            throw new BillingException(__('The storage rate does not have either the location type or non-location type setting enabled.'));
        }

        if (!empty($locationTypes)) {
            Location::whereIn('location_type_id', $locationTypes)
                ->whereIn('warehouse_id', $threepl->warehouses->pluck('id'))
                ->where(function (Builder $query) use ($invoice) {
                    $query->whereNull('deleted_at')
                        ->orWhere('deleted_at', '>=', $invoice->period_start);
                })
                ->chunkById(100, $calculateByPeriod);
        }

        if ($appliesToNoLocationType) {
            Location::whereNull('location_type_id')
                ->whereIn('warehouse_id', $threepl->warehouses->pluck('id'))
                ->where(function (Builder $query) use ($invoice) {
                    $query->whereNull('deleted_at')
                        ->orWhere('deleted_at', '>', $invoice->period_start);
                })
                ->chunkById(100, $calculateByPeriod);
        }

        Log::channel('billing')->info('[BillingRate] End ' . $this::$rateType);
    }

    protected function getDateStart(BillingRate $rate, Invoice $invoice): Carbon
    {
        $dateStart = null;
        $latestInvoiceLineItem = InvoiceLineItem::where('billing_rate_id', $rate->id)
            ->whereHas('invoice', function (Builder $query) use ($invoice) {
                $query->where('customer_id', $invoice->customer_id);
            })
            ->orderBy('period_end', 'desc')
            ->first();

        if ($latestInvoiceLineItem) {
            // If the period end is less than date start, that means days were skipped.
            $uncalculatedWeek = Carbon::parse($latestInvoiceLineItem->period_end)->lt($invoice->period_start);

            if ($uncalculatedWeek) {
                $dateStart = $latestInvoiceLineItem->period_end;
            }
        } else {
            // No item means that date range (ex.: 2021-01-01 - 2021-01-04) wasn't enough to be a week, month.
            $latestBill = Invoice::whereHas('rateCards', function (Builder $query) use ($invoice) {
                $rateCardIds = $invoice->rateCards()->pluck('rate_card_id')->toArray();
                $query->whereIn('rate_card_id', $rateCardIds);
            })
                ->where('customer_id', $invoice->customer_id)
                ->where('period_end', $invoice->period_start) // TODO: This will never be true.
                ->orderBy('period_end', 'desc')
                ->first();

            if ($latestBill) {
                $dateStart = $latestBill->period_start;
            }
        }

        $dateStart = $dateStart ?? $invoice->period_start;

        return is_string($dateStart) ? Carbon::parse($dateStart) : $dateStart;
    }

    protected function getLocationsPerDay(Invoice $invoice, Carbon $dateStart, Collection $locations): array
    {
        $dateLocationArray = [];

        foreach (CarbonPeriod::create($dateStart, '1 day', $invoice->period_end) as $billedDate) {
            foreach ($locations as $location) {
                $locationArray = $location->toArray();
                $locationArray['days_sum'] = $this->getTotalProductsByDate($location, $billedDate, $invoice);
                $dateLocationArray[$billedDate->format('Y-m-d')][] = $locationArray;
            }
        }

        return $dateLocationArray;
    }

    protected function calculateDaily(BillingRate $rate, Invoice $invoice, Collection $locations): void
    {
        $settings = $rate->settings;

        foreach (CarbonPeriod::create($invoice->period_start, '1 day', $invoice->period_end) as $billedDate) {
            foreach ($locations as $location) {
                $total = $this->getTotalProductsByDate($location, $billedDate, $invoice);

                if ($total > 0) {
                    $description = 'Daily charge for the period ' . $billedDate->format('Y-m-d')
                        . ', location: ' . $location->name;
                    $settings['location_type_id'] = $location->location_type_id;

                    app('invoice')->createInvoiceLineItem($description, $invoice, $rate, $settings, 1, $billedDate);
                }
            }
        }
    }

    /**
     * @todo We need to change this so that it charges for finished weeks within the period.
     */
    protected function calculateWeekly(BillingRate $rate, Invoice $invoice, array &$dateLocationArray): void
    {
        $settings = $rate->settings;
        $weekArray = $this->getWeeks($dateLocationArray);

        foreach ($weekArray as $startOfWeek => $weekItems) {
            if (!empty($weekItems)) {
                foreach ($weekItems as $item) {
                    [$startOfWeek, $endOfWeek] = $this->getWeekDatePeriod(Carbon::parse($startOfWeek));
                    $description = 'Weekly charge for week ' . $startOfWeek . ' to ' . $endOfWeek . ', location: ' . $item['name'];
                    $settings['location_type_id'] = $item['location_type_id'];

                    app('invoice')->createInvoiceLineItem($description, $invoice, $rate, $settings, 1, $invoice->period_end);
                }
            }
        }
    }

    protected function getWeeks(array &$dateLocationArray): array
    {
        $weekArray = [];

        foreach ($dateLocationArray as $key => $dayItems) {
            [$startOfWeek] = $this->getWeekDatePeriod(Carbon::parse($key));

            if (!isset($weekArray[$startOfWeek])) {
                $weekArray[$startOfWeek] = [];
            }

            foreach ($dayItems as $item) {
                if ($item['days_sum'] > 0) {
                    $weekArray[$startOfWeek][$item['id']] = $item;
                }
            }
        }

        // Pops a week off, to make sure not to use values were day selected is in the week.
        array_pop($weekArray);

        return $weekArray;
    }

    protected function getWeekDatePeriod(Carbon $date): array
    {
        $yearWeek = $date->format('W');
        $startOfWeek = $date->startOfWeek()->toDateString();
        $endOfWeek = $date->endOfWeek()->toDateString();

        return [$startOfWeek, $endOfWeek, $yearWeek];
    }

    protected function calculateMonthly(BillingRate $rate, Invoice $invoice, Carbon $dateStart, Collection $locations): void
    {
        $settings = $rate->settings;
        $monthsToBill = $this->findBillableMonths($dateStart, $invoice->period_end);

        $usageByDay = collect($monthsToBill)->map(
            fn (Carbon $billableMonth) => $this->getLocationsPerDayWithEndDate(
                $invoice, $billableMonth->copy()->startOfMonth(), $billableMonth->copy()->endOfMonth(), $locations
            )
        )
            // We have to collapse the collection to remove the outer layer of the array
            ->collapse()
            ->toArray();

        $monthlyUsage = $this->getMonthlyUsage($usageByDay);

        foreach ($monthsToBill as $billableMonth) {
            $monthKey = $billableMonth->format('Y-m');

            if (!isset($monthlyUsage[$monthKey])) {
                continue;
            }

            foreach ($monthlyUsage[$monthKey] as $usedLocation) {
                if (empty($usedLocation)) {
                    continue;
                }

                $periodEnd = $billableMonth->copy()->addMonth();
                $settings['location_type_id'] = $usedLocation['location_type_id'];
                $description = $this->makeMonthlyLineItemDescription($billableMonth, $usedLocation['name']);

                app('invoice')->createInvoiceLineItem($description, $invoice, $rate, $settings, 1, $periodEnd);
            }
        }
    }

    function findBillableMonths(Carbon $startDate, Carbon $endDate): array
    {
        $billingMonths = [];

        // Create a period from the start date to the end date, iterating by month
        foreach (CarbonPeriod::create($startDate->copy()->startOfMonth(), '1 month', $endDate->copy()->endOfMonth()) as $date) {
            $lastDayOfMonth = $date->copy()->endOfMonth();

            if ($lastDayOfMonth->between($startDate, $endDate) || $lastDayOfMonth->isSameDay($startDate) || $lastDayOfMonth->isSameDay($endDate)) {
                $billingMonths[] = $lastDayOfMonth->startOfMonth(); // You can customize the format as needed
            }
        }

        return $billingMonths;
    }

    protected function getLocationsPerDayWithEndDate(Invoice $invoice, Carbon $dateStart, Carbon $endDate, Collection $locations): array
    {
        $dateLocationArray = [];

        foreach (CarbonPeriod::create($dateStart, '1 day', $endDate) as $billedDate) {
            foreach ($locations as $location) {
                $locationArray = $location->toArray();
                $locationArray['days_sum'] = $this->getTotalProductsByDate($location, $billedDate, $invoice);
                $dateLocationArray[$billedDate->format('Y-m-d')][] = $locationArray;
            }
        }

        return $dateLocationArray;
    }

    private function makeMonthlyLineItemDescription(Carbon $billableMonth, string $locationName): string
    {
        $from = $billableMonth->format('Y-m-d');
        $to = $billableMonth->copy()->endOfMonth()->format("Y-m-d");

        return "Monthly charge for the period $from - $to, location: $locationName";
    }

    protected function getMonthlyUsage(array &$dateLocationArray): array
    {
        $monthArray = [];

        foreach ($dateLocationArray as $key => $dayItems) {
            $month = Carbon::parse($key)->format('Y-m');

            if (!isset($monthArray[$month])) {
                $monthArray[$month] = [];
            }

            foreach ($dayItems as $item) {
                if (isset($item['id']) && $item['days_sum'] > 0) {
                    $monthArray[$month][$item['id']] = $item;
                }
            }
        }

        return $monthArray;
    }

    /**
     * @param  mixed  $location
     * @param  CarbonInterface|null  $billedDate
     * @param  Invoice  $invoice
     * @return mixed
     */
    public function getTotalProductsByDate(
        mixed $location,
        ?CarbonInterface $billedDate,
        Invoice $invoice
    ): mixed {
        $total = 0;

        foreach ($invoice->customer->products as $product) {
            $log = $product->inventoryLogs()
                ->where('location_id', $location->id)
                ->whereDate('created_at', '<', $billedDate->copy()->addDay()->toDateString())
                ->orderByDesc('created_at')
                ->first();

            if (! $log) {
                continue;
            }

            $currentQuantity = $log->quantity_on_hand;
            $maxQuantity = null;

            // TODO: Don't iterate every single change - get the important ones.
            foreach ($this->inventoryChanges($product, $location, $billedDate)->groupBy('formatted_date') as $changeDate => $inventoryChanges) {
                if ($changeDate == $billedDate->toDateString()) {
                    foreach ($inventoryChanges as $inventoryChange) {
                        $quantity = max($inventoryChange->previous_on_hand, $inventoryChange->new_on_hand);

                        if ($quantity > $maxQuantity) {
                            $maxQuantity = $quantity;
                        }
                    }

                    if ($inventoryChanges->count() > 1) {
                        $key = array_search($inventoryChanges->max('id'), array_column($inventoryChanges->toArray(), 'id'));
                        $newestQuantityByChange = $inventoryChanges[$key];
                        $currentQuantity = $newestQuantityByChange->new_on_hand;
                    }
                } else {
                    // TODO: What's this code for? Aren't we getting changes just for the requested date?
                    foreach ($inventoryChanges as $inventoryChange) {
                        $currentQuantity -= $inventoryChange->quantity;
                    }
                }
            }

            if (empty($maxQuantity)) {
                $missing = $this->inventoryChanges($product, $location, $billedDate, true)->first();
                $currentQuantity = $missing ? $missing->previous_on_hand + $missing->quantity : 0;
            }

            $total += $maxQuantity ?? $currentQuantity;
        }

        return $total;
    }

    public function inventoryChanges(Product $product, Location $location, $date = null, $getMissing = false)
    {
        $changes = InventoryLog::where('location_id', $location->id)
            ->where('product_id', $product->id);

        if ($date && !$getMissing) {
            $changes->whereDate('updated_at', '>=', $date);
        } elseif ($date && $getMissing) {
            $changes->whereDate('updated_at', '<=', $date)->limit(1);
        }

        $changes->select(
            'inventory_logs.*',
            DB::raw('DATE_FORMAT(updated_at, "%Y-%m-%d") as formatted_date')
        );

        return $changes->orderBy('updated_at', 'DESC')->orderBy('id', 'DESC')->get();
    }
}
