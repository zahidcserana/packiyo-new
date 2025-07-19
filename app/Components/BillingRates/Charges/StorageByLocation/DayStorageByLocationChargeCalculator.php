<?php

namespace App\Components\BillingRates\Charges\StorageByLocation;

use App\Models\LocationType;

class DayStorageByLocationChargeCalculator extends StorageByLocationChargeCalculator
{
    protected function billingPeriod(): BillingPeriod
    {
        return new BillingPeriod(
            $this->chargeDate->timezone,
            $this->chargeDate->startOfDay(),
            $this->chargeDate->endOfDay()
        );
    }

    protected function shouldCharge(): bool
    {
        return true;
    }
}
