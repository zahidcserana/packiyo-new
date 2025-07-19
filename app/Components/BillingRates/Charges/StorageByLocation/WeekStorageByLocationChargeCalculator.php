<?php

namespace App\Components\BillingRates\Charges\StorageByLocation;

use App\Components\BillingRates\Helpers\BillingPeriodHelper;

class WeekStorageByLocationChargeCalculator extends StorageByLocationChargeCalculator
{
    protected function billingPeriod(): BillingPeriod
    {
        return new BillingPeriod(
            $this->chargeDate->timezone,
            $this->chargeDate->startOfWeek(),
            $this->chargeDate->endOfWeek()
        );
    }

    protected function shouldCharge(): bool
    {
        return BillingPeriodHelper::chargeDateShouldBeChargeByWeek($this->chargeDate);
    }
}
