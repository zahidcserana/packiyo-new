<?php

namespace App\Components\BillingRates\Charges\StorageByLocation;

use Carbon\CarbonImmutable;
use Carbon\CarbonTimeZone;

class BillingPeriod
{
    public function __construct(
        public readonly CarbonTimeZone $timezone,
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to
    ) {
    }
}
