<?php

namespace App\Components\BillingRates;

use App\Models\Invoice;
use App\Models\BillingRate;

Interface BillingRateInterface
{
    public function calculate(BillingRate $rate, Invoice $invoice): void;

    public function tracksBilledOperations(): bool;

    public function resetBilledOperations(): void;
}
