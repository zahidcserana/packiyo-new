<?php

namespace App\Components\Invoice\DataTransferObjects;

class StorageBillableOperationDto extends BillableOperationDto
{
    public function isFulfillmentOperation(): bool
    {
        return false;
    }

    public function isReceivingOperation(): bool
    {
        return false;
    }

    public function isStorageOperation(): bool
    {
        return true;
    }

    public function getType(): string
    {
        return self::STORAGE_TYPE;
    }
}
