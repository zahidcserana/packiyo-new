<?php

namespace App\Components\BillingRates\StorageByLocationRate;

use App\Models\BillingRate;
use App\Models\Invoice;

interface LocationsUsageBillingCalculator
{
    public function calculate(BillingRate $rate, Invoice $invoice): void;
}
