<?php

namespace App\Models\Automations;

/**
 * Which line items to evaluate.
 */
enum AppliesToLineItems: string
{
    case ALL = 'all';
    case SOME = 'some';
}
