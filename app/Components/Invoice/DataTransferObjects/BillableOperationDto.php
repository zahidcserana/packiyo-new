<?php

namespace App\Components\Invoice\DataTransferObjects;

use App\Models\BillingRate;

abstract class BillableOperationDto
{
    const FULFILLMENT_TYPE = 'fulfillment';
    const RECEIVING_TYPE = 'receiving';
    const STORAGE_TYPE = 'storage';
    abstract public function isFulfillmentOperation(): bool;
    abstract public function isReceivingOperation(): bool;
    abstract public function isStorageOperation(): bool;
    abstract public function getType(): string;

    public array $billingRates = [];
    public function getBillingRates(): array
    {
        return $this->billingRates;
    }

    public function setBillingRate(BillingRate $billingRates): void
    {
        $this->billingRates[] = $billingRates;
    }

    public function setBillingRates(array $billingRates): void
    {
        $this->billingRates = array_merge($this->billingRates, $billingRates);
    }
}
