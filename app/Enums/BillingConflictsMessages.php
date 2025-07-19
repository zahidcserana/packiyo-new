<?php

namespace App\Enums;

use App\Traits\TranslatableEnumTrait;

enum BillingConflictsMessages :string
{
    use TranslatableEnumTrait;
    case DEFAULT_MESSAGE = 'Conflicts with existing fee';
}
