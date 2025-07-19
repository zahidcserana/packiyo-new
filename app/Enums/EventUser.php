<?php

namespace App\Enums;

use App\Traits\TranslatableEnumTrait;

enum EventUser: string
{
    use TranslatableEnumTrait;
    case CUSTOMER_IS_3PL = '3PL';
    case CUSTOMER_IS_3PL_CLIENT = '3PL Client';
}
