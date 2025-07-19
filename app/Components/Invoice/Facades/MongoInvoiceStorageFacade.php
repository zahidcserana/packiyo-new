<?php

namespace App\Components\Invoice\Facades;

use App\Components\BillingRates\Charges\StorageByLocation\BillingPeriod;
use App\Components\BillingRates\Charges\StorageByLocation\StorageByLocationChargeComponent;
use App\Components\BillingRates\Helpers\BillingPeriodHelper;
use App\Components\BillingRates\StorageByLocationRate\LocationsUsageBillingPeriod;
use App\Models\BillingRate;
use App\Models\CacheDocuments\InvoiceCacheDocument;
use App\Models\CacheDocuments\StorageByLocationChargeCacheDocument;
use App\Models\CacheDocuments\WarehouseOccupiedLocationTypesCacheDocument;
use App\Models\Customer;
use App\Models\RateCard;
use App\Traits\CacheDocumentTrait;
use App\Traits\InvoiceBillableOperationTrait;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MongoInvoiceStorageFacade
{
    use InvoiceBillableOperationTrait, CacheDocumentTrait;

    public function __construct(
        private readonly StorageByLocationChargeComponent $storageByLocationChargeComponent
    )
    {
    }

    const CHUNK_SIZE = 100;

    public function generateInvoiceItemForStorage(InvoiceCacheDocument $invoiceCacheDocument, array $invoiceBillingRates): bool
    {
        $result = true;
        $periodStart = Carbon::parse($invoiceCacheDocument->period_start)->startOfDay();
        $periodEnd = Carbon::parse($invoiceCacheDocument->period_end)->endOfDay();
        $periodStart = $periodStart->subDay();
        $daysInPeriod = $periodStart->diffInDays($periodEnd) + 1;

        $customer = Customer::whereId($invoiceCacheDocument->customer_id)->first();
        $warehouseIds = $customer->parent->warehouses->pluck('id')->toArray();

        $query = WarehouseOccupiedLocationTypesCacheDocument::query()
            ->whereBetween('calendar_date',
                [
                    $periodStart->toDateString(),
                    $periodEnd->toDateString()
                ])
            ->where('customer_id', $customer->id)
            ->whereIn('warehouse_id', $warehouseIds);

        if ($query->count() == 0 || ($query->count() != $daysInPeriod)) {
            return false;
        }

        $query->chunk(self::CHUNK_SIZE, function ($records) use ($invoiceCacheDocument, &$invoiceBillingRates) {
            /* @var WarehouseOccupiedLocationTypesCacheDocument $record */
            foreach ($records as $record) {

                $calculateDate = new CarbonImmutable($record->calendar_date);
                $chargeDocs = [];
                $billingRateToAdd = [];
                $billingRateToDiscardFromCharges = [];
                $chargesToAdd = [];
                $chargesToDelete = [];
                $chargesDTOs = [];
                $billingRateToReCalculateAfter = []; //warehouse document won't be updated with these element
                $billingRateToCalculateAfter = [];

                $chargeDocs = $this->getChargeDocuments($record, $chargeDocs, $invoiceBillingRates, $calculateDate);
                if (empty($record->getCalculatedBillingRates())) {
                    Log::debug(sprintf("No calculated billing rates in doc %s with info %s, continue for next one", $record->_id, $record->getDocumentInformation()));
                    continue;
                }

                $docBillingRates = collect($record->getCalculatedBillingRates());
                foreach ($invoiceBillingRates as $billingRate) {

                    if ($this->skipBillingRateByCalculatedDate($billingRate, $calculateDate)) {
                        continue;
                    }

                    $billingRateInScope = $docBillingRates->where('billing_rate_id', $billingRate->id)->first();
                    if (!$billingRateInScope) {
                        Log::debug("Generating new charges by missing billing rate in calculation cache");
                        $billingRateToCalculateAfter[] = $billingRate;
                        continue;
                    }

                    if ($this->isBillingRateUpdated($billingRateInScope, $billingRate)) {
                        Log::debug("Generating new charges by billing rate updated");
                        $billingRateToReCalculateAfter[] = $billingRate;
                        $billingRateToDiscardFromCharges[] = $billingRate;
                        continue;
                    }

                    if (!($this->getChargesByBillingRate($chargeDocs, $billingRate)->count() == $billingRateInScope['charges'])) {
                        //charges count doesnt match
                        Log::debug("Generating new charges by billing rate charges count mismatch");
                        $billingRateToReCalculateAfter[] = $billingRate;
                        $billingRateToDiscardFromCharges[] = $billingRate;
                    }
                }

                if (!empty($billingRateToReCalculateAfter) || !empty($billingRateToCalculateAfter)) {
                    //calculate missing billing rate charges
                    //recalculate by billingRate
                    foreach ($billingRateToReCalculateAfter as $billingRateToCalculate) {
                        $chargesToAdd = $this->calculateMissingBillingRateCharges(
                            $record,
                            $billingRateToCalculate,
                            $chargeDocs[$billingRateToCalculate->type],
                            $chargesToAdd
                        );
                    }

                    //calculate by billingRate
                    foreach ($billingRateToCalculateAfter as $billingRateToCalculate) {
                        $chargesToAdd = $this->calculateMissingBillingRateCharges(
                            $record,
                            $billingRateToCalculate,
                            $chargeDocs[$billingRateToCalculate->type],
                            $chargesToAdd,
                            false
                        );
                        $chargesResultCount = 0;
                        foreach ($chargesToAdd as $chargeToAdd) {
                            $chargesResultCount += $chargeToAdd->count();
                        }
                        $billingRateToAdd[] = [
                            'billing_rate_id' => $billingRateToCalculate->id,
                            'calculated_at' => $billingRateToCalculate->updated_at->toIso8601String(),
                            'charges' => $chargesResultCount
                        ];
                    }

                    $this->removeChargesByBillingRates($billingRateToDiscardFromCharges, $chargeDocs, $chargesToDelete);
                }

                $charges = $this->getCharges($chargesToAdd, $chargeDocs, $chargesDTOs);

                //create invoice line items
                foreach ($charges as $charge) {
                    $charge['invoice_id'] = $invoiceCacheDocument['id'];
                    app('invoice')->createInvoiceLineItemOnTheFly($charge);
                }
                $this->updateCacheDocuments($billingRateToAdd, $record, $chargesToDelete);
            }
        });

        return $result;
    }

    private function getChargeDocuments(
        WarehouseOccupiedLocationTypesCacheDocument $record,
        array $chargeDocs,
        array $billingRates,
        CarbonImmutable $calendarDate
    ): array
    {
        $charges = [];
        foreach ($billingRates as $billingRate) {
            if ($this->skipBillingRateByCalculatedDate($billingRate, $calendarDate)) {
                continue;
            }

            $billingPeriod = $this->getBillingPeriod(
                LocationsUsageBillingPeriod::from($billingRate->settings['period']),
                $calendarDate
            );
            $charges[] = $this->getChargesByDocumentAndBillingRate($record, $billingRate, $billingPeriod);
        }

        $mergedCollection = collect();

        foreach ($charges as $collection) {
            $mergedCollection = $mergedCollection->merge($collection);
        }
        $chargeDocs[BillingRate::STORAGE_BY_LOCATION] = $mergedCollection;

        return $chargeDocs;
    }

    private function getCharges(array $chargesToAdd, array $chargeDocs, array $chargesDTOs): Collection
    {
        //add chargesToAdd
        foreach ($chargesToAdd as $chargeToAdd) {
            // if add it more bill rates consider here
            $chargeDocs[BillingRate::STORAGE_BY_LOCATION] = $chargeDocs[BillingRate::STORAGE_BY_LOCATION]->merge($chargeToAdd);
        }

        foreach ($chargeDocs as $key => $value) {
            $chargesDTOs[$key] = $value->map(function ($el) {
                return $el->getCharges();
            });
        }
        return $this->formatCharges(
            $chargesDTOs[BillingRate::STORAGE_BY_LOCATION]
        );
    }

    private function formatCharges(Collection $chargesDTOs): Collection
    {
        $charges = [];
        foreach ($chargesDTOs as $dto) {
            $charges[] = $dto;
        }

        $charges = $this->billableOperationFlattenArray($charges);
        return collect($charges)->filter(fn($element) => !empty($element));
    }

    private function calculateMissingBillingRateCharges(
        WarehouseOccupiedLocationTypesCacheDocument $record,
        BillingRate $billingRateToCalculate,
        Collection $items,
        array $chargesToAdd,
        bool $recalculate = true
    ): array
    {
        $allCharges = $this->calculateBillingRate($record, $billingRateToCalculate, $recalculate);
        $itemsForThatBillingRate = $items->filter(fn($item) => $item['billing_rate']['id'] == $billingRateToCalculate->id);
        $chargesToAdd[] = $allCharges->diff($itemsForThatBillingRate);
        return $this->filterEmptyCharges($chargesToAdd);
    }

    private function calculateBillingRate(
        WarehouseOccupiedLocationTypesCacheDocument $record,
        BillingRate $billingRateToCalculate,
        bool $recalculate
    )
    {
        $calendarDate = new CarbonImmutable($record->calendar_date);
        $billingPeriod = $this->getBillingPeriod(
            LocationsUsageBillingPeriod::from($billingRateToCalculate->settings['period']),
            $calendarDate
        );

        $this->storageByLocationChargeComponent->charge(
            $billingRateToCalculate,
            $record,
            $calendarDate,
            $recalculate
        );

        return $this->getChargesByDocumentAndBillingRate(
            $record,
            $billingRateToCalculate,
            $billingPeriod
        );
    }

    /**
     * @param WarehouseOccupiedLocationTypesCacheDocument $record
     * @param BillingRate $billingRate
     * @param BillingPeriod $billingPeriod
     * @return mixed
     */
    private function getChargesByDocumentAndBillingRate(
        WarehouseOccupiedLocationTypesCacheDocument $record,
        BillingRate $billingRate,
        BillingPeriod $billingPeriod
    ): Collection
    {
        return StorageByLocationChargeCacheDocument::query()
            ->where('customer_id', $record->customer_id)
            ->where('warehouse_id', $record->warehouse_id)
            ->where('rate_card_id', $billingRate->rate_card_id)
            ->where('billing_rate_id', $billingRate->id)
            ->period($billingPeriod->from, $billingPeriod->to)
            ->get();
    }

    private function updateCacheDocuments(
        array $billingRateToAdd,
        WarehouseOccupiedLocationTypesCacheDocument $record,
        array $chargesToDelete
    ): void
    {
        //update warehouse document with new calculated billing rates
        if (!empty($billingRateToAdd)) {
            $this->updateBillingRates($record, $billingRateToAdd);
            $record->save();
        }

        //deletes charges marked for deletion
        if (!empty($chargesToDelete)) {
            //delete charges in
            foreach ($chargesToDelete as $charge) $charge->delete();
        }

    }

    private function skipBillingRateByCalculatedDate(BillingRate $billingRate, CarbonImmutable $calculateDate): bool
    {
        if ($billingRate->settings['period'] == 'month' && !BillingPeriodHelper::chargeDateShouldBeChargeByMonth($calculateDate)) {
            return true;
        }
        if ($billingRate->settings['period'] == 'week' && !BillingPeriodHelper::chargeDateShouldBeChargeByWeek($calculateDate)) {
            return true;
        }
        return false;
    }

    /**
     * @param LocationsUsageBillingPeriod $period
     * @param CarbonImmutable $calendarDate
     * @return BillingPeriod
     */
    private function getBillingPeriod(LocationsUsageBillingPeriod $period, CarbonImmutable $calendarDate): BillingPeriod
    {
        switch ($period) {
            case LocationsUsageBillingPeriod::Day:
                $billingPeriod = new BillingPeriod(
                    $calendarDate->timezone,
                    $calendarDate->startOfDay(),
                    $calendarDate->endOfDay()
                );
                break;
            case LocationsUsageBillingPeriod::Week:
                $billingPeriod = new BillingPeriod(
                    $calendarDate->timezone,
                    $calendarDate->startOfWeek(),
                    $calendarDate->endOfWeek()
                );
                break;
            case LocationsUsageBillingPeriod::Month:
                $billingPeriod = new BillingPeriod(
                    $calendarDate->timezone,
                    $calendarDate->startOfMonth(),
                    $calendarDate->endOfMonth()
                );
                break;
        }
        return $billingPeriod;
    }
}
