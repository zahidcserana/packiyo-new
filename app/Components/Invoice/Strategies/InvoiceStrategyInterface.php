<?php

namespace App\Components\Invoice\Strategies;

use App\Models\Invoice;
use App\Models\User;

interface InvoiceStrategyInterface
{
    public function bill(Invoice $invoice, array $billableOperations = [] , ?User $user = null): bool;
}
