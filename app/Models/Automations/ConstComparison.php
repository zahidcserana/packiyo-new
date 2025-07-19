<?php

namespace App\Models\Automations;

/**
 * Operators to compare fixed constant values, such as enums.
 */
enum ConstComparison: string
{
    case IS_ONE_OF = 'is_one_of';
    case IS_NONE_OF = 'is_none_of';
}
