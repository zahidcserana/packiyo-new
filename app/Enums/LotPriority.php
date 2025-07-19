<?php

namespace App\Enums;

use App\Traits\TranslatableEnumTrait;

enum LotPriority: string
{
    use TranslatableEnumTrait;

    case NONE = '';
    case FEFO = 'FEFO';
    case FIFO = 'FIFO';
    case LIFO = 'LIFO';
}
