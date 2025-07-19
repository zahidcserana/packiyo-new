<?php

namespace App\Components\BillingRates\Charges\StorageByLocation;

use App\Components\BillingRates\StorageByLocationRate\LocationsUsageBillingPeriod;
use App\Components\InventoryLogComponent;
use App\Models\BillingRate;
use App\Models\CacheDocuments\WarehouseOccupiedLocationTypesCacheDocument;
use App\Models\Customer;
use App\Models\Warehouse;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;

class StorageByLocationChargeComponent
{
    public function __construct(private readonly InventoryLogComponent $inventoryLogComponent)
    {
    }

    public function calculate(
        Customer $client,
        Warehouse $warehouse,
        Carbon $calendarDate
    ): void
    {
        $occupiedLocationTypes = $this->inventoryLogComponent->calculateOccupiedLocationsByDay(
            $client,
            $warehouse,
            $calendarDate
        ); // generates OccupiedLocationLogs
        $this->chargeBillingRates($occupiedLocationTypes);
    }

    private function chargeBillingRates(WarehouseOccupiedLocationTypesCacheDocument $occupiedLocationTypes): void
    {
        $clientBillingRates = BillingRate::query()
            ->whereHas('rateCard.customers', function (Builder $query) use ($occupiedLocationTypes) {
                $query->where('customer_id', $occupiedLocationTypes->customer_id);
            })
            ->where('is_enabled', 1)
            ->where('type', BillingRate::STORAGE_BY_LOCATION)
            ->orderBy('settings->if_no_other_rate_applies')
            ->get();

        $clientBillingRates->each(fn (BillingRate $rate) =>
            $this->charge($rate, $occupiedLocationTypes, new CarbonImmutable($occupiedLocationTypes->calendar_date))
        );
    }

    public function charge(BillingRate $rate, WarehouseOccupiedLocationTypesCacheDocument $warehouseOccupiedLocations, CarbonImmutable $chargeDate, bool $recalculate = false): void
    {
        $period = LocationsUsageBillingPeriod::from($rate->settings['period']);

        // Now, if the period is day, we generate the charge (if the client has occupied locations) for the day.
        // If the period is week, we check if the previous week has ended, and we generate the charge for the week.
        // If the period is month, we check if the previous month has ended, and we generate the charge for the month.
        // For example: if the period is day, and the client has occupied locations, we generate the charge for the day.
        // After that, we check if we also generate the charge for the previous day. If not, we generate it.
        // Every day, we need to check if we generated this week's charge, and if not, we generate it for the previous week, until Sunday.

        match ($period) {
            LocationsUsageBillingPeriod::Day => (App::make(DayStorageByLocationChargeCalculator::class, [
                'rate' => $rate,
                'warehouseOccupiedLocations' => $warehouseOccupiedLocations,
                'chargeDate' => $chargeDate,
                'recalculate' => $recalculate
            ]))->charge(),
            LocationsUsageBillingPeriod::Week => (
            App::make(WeekStorageByLocationChargeCalculator::class, [
                'rate' => $rate,
                'warehouseOccupiedLocations' => $warehouseOccupiedLocations,
                'chargeDate' => $chargeDate,
                'recalculate' => $recalculate
            ])
            )->charge(),
            LocationsUsageBillingPeriod::Month => (
            App::make(MonthStorageByLocationChargeCalculator::class, [
                'rate' => $rate,
                'warehouseOccupiedLocations' => $warehouseOccupiedLocations,
                'chargeDate' => $chargeDate,
                'recalculate' => $recalculate
            ])
            )->charge(),
        };
    }
}
