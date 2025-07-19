<?php

namespace App\Models\Automations;

/**
 * Order numeric fields that can be used in automations.
 */
enum OrderNumberField: string
{
    case SUBTOTAL = 'subtotal';
    case SHIPPING = 'shipping';
    case TAX = 'tax';
    case TOTAL = 'total';
}
