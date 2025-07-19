<?php

namespace App\Components\BillingRates\PickingStategies;

use App\Models\BillingRate;
use App\Models\Invoice;

interface PickingStrategyInterface
{
    public function calculateByRateAndInvoice(BillingRate $rate, Invoice $invoice): void;

    public function getSettingsFromBillingRate(BillingRate $rate):array ;
}
