<?php

namespace App\Components\BillingRates\Helpers;

use Carbon\CarbonImmutable;

class BillingPeriodHelper
{
    public static function chargeDateShouldBeChargeByWeek(CarbonImmutable $chargeDate): bool
    {
        return $chargeDate->addDay()->isMonday();
    }
    public static function chargeDateShouldBeChargeByMonth(CarbonImmutable $chargeDate): bool
    {
        return $chargeDate->addDay()->day === 1;
    }
}
