<?php

namespace App\Components\BillingRates\Charges\StorageByLocation;

use App\Components\BillingRates\Helpers\BillingPeriodHelper;

class MonthStorageByLocationChargeCalculator extends StorageByLocationChargeCalculator
{
    protected function billingPeriod(): BillingPeriod
    {
        return new BillingPeriod(
            $this->chargeDate->timezone,
            $this->chargeDate->startOfMonth(),
            $this->chargeDate->endOfMonth()
        );
    }

    protected function shouldCharge(): bool
    {
        return BillingPeriodHelper::chargeDateShouldBeChargeByMonth($this->chargeDate);
    }
}
