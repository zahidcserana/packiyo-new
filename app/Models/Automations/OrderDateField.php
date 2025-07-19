<?php

namespace App\Models\Automations;

/**
 * Order text fields that can be used in automations.
 * Region codes:
 *  - "002": Africa
 *  - "009": Oceania
 *  - "019": America
 *  - "142": Asia
 *  - "150": Europe
 *  - "":    Other
 */
enum OrderDateField: string
{
    use HasChoices;

    case HOLD_UNTIL = 'hold_until';
    case SHIP_BEFORE = 'ship_before';
    case SCHEDULED_DELIVERY = 'scheduled_delivery';
}
