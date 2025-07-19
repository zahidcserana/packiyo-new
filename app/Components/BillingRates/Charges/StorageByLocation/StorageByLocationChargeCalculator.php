<?php

namespace App\Components\BillingRates\Charges\StorageByLocation;

use App\Components\BillingRates\StorageByLocationRate\LocationsUsageBillingPeriod;
use App\Models\BillingRate;
use App\Models\CacheDocuments\StorageByLocationChargeCacheDocument;
use App\Models\CacheDocuments\WarehouseOccupiedLocationTypesCacheDocument;
use App\Models\LocationType;
use App\Traits\CacheDocumentTrait;
use App\Traits\MongoBillingCalculatorTrait;
use Carbon\CarbonImmutable;

abstract class StorageByLocationChargeCalculator
{
    use MongoBillingCalculatorTrait, CacheDocumentTrait;

    public array $billingRatesCharge;

    public function __construct(
        protected readonly BillingRate $rate,
        protected readonly WarehouseOccupiedLocationTypesCacheDocument $warehouseOccupiedLocations,
        protected readonly CarbonImmutable $chargeDate,
        private readonly LocationTypesCache $locationTypesCache,
        private $recalculate
    )
    {
        $this->billingRatesCharge = [];
    }

    public function charge(): void
    {
        $billingPeriod = $this->billingPeriod();

        if ($this->shouldCharge() && !$this->hasAlreadyCharged($billingPeriod) && $this->hasAllNecessaryDataInDocsDb($billingPeriod)) {
            $this->chargePeriod($billingPeriod);
        }
    }

    abstract protected function billingPeriod(): BillingPeriod;

    abstract protected function shouldCharge(): bool;

    protected function description(LocationType $locationType): string
    {
        $periodName = LocationsUsageBillingPeriod::from($this->rate->settings['period'])->name();
        $fromDateTime = $this->billingPeriod()->from->toDateTimeString();
        $toDateTime = $this->billingPeriod()->to->toDateTimeString();
        $locationTypeName = $locationType->name;
        $warehouseName = $this->warehouseOccupiedLocations->warehouse['name'];

        return "{$periodName} charge for the period from {$fromDateTime} to {$toDateTime} for occupying location type {$locationTypeName} in the warehouse {$warehouseName}";
    }

    private function hasAlreadyCharged(BillingPeriod $billingPeriod): bool
    {
        return StorageByLocationChargeCacheDocument::query()
            ->where('customer_id', $this->warehouseOccupiedLocations->customer_id)
            ->where('warehouse_id', $this->warehouseOccupiedLocations->warehouse_id)
            ->where('rate_card_id', $this->rate->rate_card_id)
            ->where('billing_rate_id', $this->rate->id)
            ->period($billingPeriod->from, $billingPeriod->to)
            ->where('billing_rate.updated_at', '>=', $this->rate->updated_at->toIso8601String())
            ->exists();
    }

    private function hasAllNecessaryDataInDocsDb(BillingPeriod $period): bool
    {
        $neededCalculations = $period->from->diff($period->to)->days + 1;

        $periodCalculations = WarehouseOccupiedLocationTypesCacheDocument::query()
            ->whereBetween('calendar_date', [$period->from->toDateString(), $period->to->toDateString()])
            ->where('customer_id', $this->warehouseOccupiedLocations->customer_id)
            ->where('warehouse_id', $this->warehouseOccupiedLocations->warehouse_id)
            ->count();

        return $periodCalculations >= $neededCalculations;
    }

    private function chargePeriod(BillingPeriod $period): void
    {
        $chargeableLocationTypes = json_decode($this->rate->settings['location_types'], true);
        if (!$this->recalculate) {
            $this->billingRatesCharge[] = $this->addBillingRate($this->rate);
        }
        $occupiedLocationTypesWithinPeriod = WarehouseOccupiedLocationTypesCacheDocument::query()
            ->raw(fn($collection) => $collection->aggregate([
                [
                    '$match' => [
                        "customer_id" => $this->warehouseOccupiedLocations->customer_id,
                        "warehouse_id" => $this->warehouseOccupiedLocations->warehouse_id,
                        "calendar_date" => [
                            '$gte' => $period->from->toDateString(),
                            '$lte' => $period->to->toDateString()
                        ],
                        'location_types' => ['$ne' => []]
                    ]
                ],
                [
                    '$unwind' => '$location_types'
                ],
                [
                    '$unwind' => '$location_types.occupied_locations'
                ],
                [
                    '$group' => [
                        '_id' => [
                            'location_type' => '$location_types.type',
                            'location_type_id' => '$location_types.type_id',
                            'location_name' => '$location_types.occupied_locations.location_name'
                        ]
                    ]
                ],
                [
                    '$group' => [
                        '_id' => [
                            'location_type_id' => '$_id.location_type_id',
                            'location_type' => '$_id.location_type'
                        ],
                        'occupied_locations' => ['$push' => '$_id.location_name']
                    ]
                ],
                [
                    '$project' => [
                        'location_type' => '$_id.location_type',
                        'location_type_id' => '$_id.location_type_id',
                        'occupied_locations' => 1,
                        '_id' => 0
                    ]
                ]
            ]));

        foreach ($occupiedLocationTypesWithinPeriod as $warehouseOccupiedLocationTypes) {
            if (!in_array($warehouseOccupiedLocationTypes['location_type_id'], $chargeableLocationTypes)) {
                continue;
            }

            $locationType = $this->locationTypesCache->get($warehouseOccupiedLocationTypes['location_type_id']);

            if (!$locationType) {
                continue;
            }
            if (!$this->recalculate) {
                $this->addChargeCountToBillingRate($this->rate);
            }
            (StorageByLocationChargeCacheDocument::build(
                $this->warehouseOccupiedLocations->customer_id,
                $this->warehouseOccupiedLocations->warehouse_id,
                $this->warehouseOccupiedLocations->warehouse['name'],
                $this->rate->rateCard,
                $this->rate,
                $locationType,
                count($warehouseOccupiedLocationTypes['occupied_locations']),
                $period,
                $this->description($locationType)
            ))->save();
        }
        if (!$this->recalculate) {
            $this->updateWarehouseDocumentBillingRates();
        }
    }

    private function updateWarehouseDocumentBillingRates(): void
    {
        $warehouseOccupiedLocations = $this->updateBillingRates(
            $this->warehouseOccupiedLocations,
            $this->billingRatesCharge
        );
        $warehouseOccupiedLocations->save();
    }
}

