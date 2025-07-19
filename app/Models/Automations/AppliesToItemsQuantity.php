<?php

namespace App\Models\Automations;

/**
 * Which line items in which to evaluate quantity.
 */
enum AppliesToItemsQuantity: string
{
    case ANY = 'any';
    case EACH = 'each';
    case NONE = 'none';
    case TOTAL = 'total';
}
