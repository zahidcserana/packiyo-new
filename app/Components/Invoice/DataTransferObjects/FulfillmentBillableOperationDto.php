<?php

namespace App\Components\Invoice\DataTransferObjects;

class FulfillmentBillableOperationDto extends BillableOperationDto
{
    public function isFulfillmentOperation(): bool
    {
        return true;
    }

    public function isReceivingOperation(): bool
    {
        return false;
    }

    public function isStorageOperation(): bool
    {
        return false;
    }

    public function getType(): string
    {
        return self::FULFILLMENT_TYPE;
    }
}
