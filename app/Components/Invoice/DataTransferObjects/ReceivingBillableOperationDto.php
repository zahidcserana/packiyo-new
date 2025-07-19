<?php

namespace App\Components\Invoice\DataTransferObjects;

class ReceivingBillableOperationDto extends BillableOperationDto
{
    public function isFulfillmentOperation(): bool
    {
        return false;
    }

    public function isReceivingOperation(): bool
    {
        return true;
    }

    public function isStorageOperation(): bool
    {
        return false;
    }

    public function getType(): string
    {
        return self::RECEIVING_TYPE;
    }
}
