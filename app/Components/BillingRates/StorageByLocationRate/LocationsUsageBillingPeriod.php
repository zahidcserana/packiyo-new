<?php

namespace App\Components\BillingRates\StorageByLocationRate;

enum LocationsUsageBillingPeriod: string
{
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';

    public function name(): string
    {
        return match ($this) {
            self::Day => 'Daily',
            self::Week => 'Weekly',
            self::Month => 'Monthly',
        };
    }
}
