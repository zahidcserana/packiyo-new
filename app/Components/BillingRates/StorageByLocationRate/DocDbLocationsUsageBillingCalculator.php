<?php

namespace App\Components\BillingRates\StorageByLocationRate;

use App\Models\BillingRate;
use App\Models\CacheDocuments\WarehouseOccupiedLocationTypesCacheDocument;
use App\Models\Invoice;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class DocDbLocationsUsageBillingCalculator implements LocationsUsageBillingCalculator
{
    public function calculate(BillingRate $rate, Invoice $invoice): void
    {
        switch (LocationsUsageBillingPeriod::from($rate->settings['period'])) {
            case LocationsUsageBillingPeriod::Day:
                $this->calculateDailyMongo($rate, $invoice);
                break;
            case LocationsUsageBillingPeriod::Week:
                $this->calculateWeeklyMongo($rate, $invoice);
                break;
            case LocationsUsageBillingPeriod::Month:
                $this->calculateMonthlyMongo($rate, $invoice);
                break;
        }
    }

    private function calculateDailyMongo(BillingRate $rate, Invoice $invoice): void
    {
        $settings = $rate->settings;

        /** @var Collection $result */
        $result = WarehouseOccupiedLocationTypesCacheDocument::raw(function ($collection) use ($rate, $invoice) {
            return $collection->aggregate([
                [
                    '$match' => [
                        'calendar_date' => [
                            '$gte' => $invoice->period_start->toDateString(),
                            '$lte' => $invoice->period_end->toDateString()
                        ],
                        'warehouse_id' => [
                            '$in' => $invoice->customer->parent->warehouses->pluck('id')->toArray(),
                        ],
                        'location_types.type_id' => [
                            '$in' => json_decode($rate->settings['location_types'])
                        ],
                        // Also check if the location_types array is not empty
                        'customer_id' => $invoice->customer_id,
                        'deleted_at' => null,
                    ]
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'location_types' => 1,
                        'calendar_date' => 1,
                    ]
                ]
            ]);
        });

        $result->each(function (WarehouseOccupiedLocationTypesCacheDocument $item) use ($invoice, $rate, $settings) {
            $billedDate = Carbon::parse($item['calendar_date']);

            foreach ($item->location_types as $occupied_location_type) {
                $location_type_id = $occupied_location_type['type_id'];

                foreach ($occupied_location_type['occupied_locations'] as $occupation) {
                    $description = 'Daily charge for the period ' . $billedDate->format('Y-m-d')
                        . ', location: ' . $occupation['location_name'];
                    $settings['location_type_id'] = $location_type_id;

                    app('invoice')->createInvoiceLineItem($description, $invoice, $rate, $settings, 1, $billedDate);
                }
            }
        });
    }

    private function calculateWeeklyMongo(BillingRate $rate, Invoice $invoice): void
    {
        $settings = $rate->settings;

        $weeks = CarbonPeriod::create($invoice->period_start, '1 week', $invoice->period_end)->toArray();

        // Pops a week off, to make sure not to use values were day selected is in the week.
        array_pop($weeks); // Remove last week

        foreach ($weeks as $week) {
            $startOfWeek = $week->startOfWeek()->toDateString();
            $endOfWeek = $week->endOfWeek()->toDateString();

            $result = $this->usedLocations(json_decode($rate->settings['location_types']), $invoice, $startOfWeek, $endOfWeek);

            $result
                ->each(function (WarehouseOccupiedLocationTypesCacheDocument $warehouseOccupiedLocationTypes) use ($invoice, $rate, $settings, $startOfWeek, $endOfWeek) {
                    $description = "Weekly charge for week $startOfWeek to $endOfWeek, location: {$warehouseOccupiedLocationTypes['location_name']}";
                    $settings['location_type_id'] = $warehouseOccupiedLocationTypes['location_type_id'];

                    app('invoice')->createInvoiceLineItem($description, $invoice, $rate, $settings, 1, $invoice->period_end);
                });
        }
    }

    private function calculateMonthlyMongo(BillingRate $rate, Invoice $invoice): void
    {
        $settings = $rate->settings;

        $monthsToBill = CarbonPeriod::create($invoice->period_start, '1 month', $invoice->period_end->copy()->subMonth());

        foreach ($monthsToBill as $month) {
            $startOfMonth = $month->startOfMonth()->toDateString();
            $endOfMonth = $month->endOfMonth()->toDateString();

            $result = $this->usedLocations(json_decode($rate->settings['location_types']), $invoice, $startOfMonth, $endOfMonth);

            $result
                ->each(function (WarehouseOccupiedLocationTypesCacheDocument $types) use ($invoice, $rate, $settings, $startOfMonth, $endOfMonth) {
                    $periodEnd = Carbon::parse($startOfMonth)->addMonth()->toDateString();
                    $description = "Monthly charge for the period $startOfMonth to $periodEnd, location: {$types['location_name']}";
                    $settings['location_type_id'] = $types['location_type_id'];

                    app('invoice')->createInvoiceLineItem($description, $invoice, $rate, $settings, 1, $invoice->period_end);
                });
        }
    }

    private function usedLocations(
        array $locationTypesIds,
        Invoice $invoice,
        string $start,
        string $end
    ): Collection {
        /** @var Collection $result */
        $result = WarehouseOccupiedLocationTypesCacheDocument::raw(function ($collection) use (
            $locationTypesIds,
            $invoice,
            $start,
            $end
        ) {
            return $collection->aggregate([
                [
                    '$match' => [
                        'calendar_date' => [
                            '$gte' => $start,
                            '$lte' => $end
                        ],
                        'warehouse_id' => [
                            '$in' => $invoice->customer->parent->warehouses->pluck('id')->toArray(),
                        ],
                        'customer.id' => $invoice->customer_id,
                        'deleted_at' => null,
                        'location_types.type_id' => [
                            '$in' => $locationTypesIds
                        ]
                    ]
                ],
                [
                    '$group' => [
                        '_id' => [
                            'location_id' => '$location_types.occupied_locations.location_id',
                        ],
                        'location_name' => ['$first' => '$location_types.occupied_locations.location_name'],
                        'location_type_id' => ['$first' => '$location_types.type_id'],
                        'customer_id' => ['$first' => '$customer.id'],
                        'customer_name' => ['$first' => '$customer.name'],
                    ]
                ],
                [
                    '$unwind' => '$_id.location_id'
                ],
                [
                    '$unwind' => '$_id.location_id'
                ],
                [
                    '$unwind' => '$location_name',
                ],
                [
                    '$unwind' => '$location_name',
                ],
                [
                    '$unwind' => '$location_type_id',
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'location_id' => '$_id.location_id',
                        'location_name' => 1,
                        'location_type_id' => 1,
                        'customer_id' => 1,
                        'customer_name' => 1,
                    ]
                ]
            ]);
        });

        return $result;
    }
}
