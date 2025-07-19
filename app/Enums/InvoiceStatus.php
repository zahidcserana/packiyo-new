<?php

namespace App\Enums;

use App\Traits\TranslatableEnumTrait;

enum InvoiceStatus: string
{
    use TranslatableEnumTrait;
    case DONE_STATUS = 'done';
    case PENDING_STATUS = 'pending';
    case CALCULATING_STATUS = 'calculating';
    case FAILED_STATUS = 'failed';
}
