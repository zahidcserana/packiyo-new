<?php

namespace App\Models\Automations;

/**
 * Order flags that can be automated.
 */
enum OrderFlag: string
{
    case ALLOCATION_HOLD = 'allocation_hold';
    case OPERATOR_HOLD = 'operator_hold';
    case PAYMENT_HOLD = 'payment_hold';
    case FRAUD_HOLD = 'fraud_hold';
    case ALLOW_PARTIAL = 'allow_partial';
    case DISABLED_ON_PICKING_APP = 'disabled_on_picking_app';
    case PRIORITY = 'priority';
    case READY_TO_PICK = 'ready_to_pick';
    case READY_TO_SHIP = 'ready_to_ship';
    case IS_WHOLESALE = 'is_wholesale';
}
