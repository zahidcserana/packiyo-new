<?php

namespace App\Models\Automations;

/**
 * Time units for automations.
 */
enum TimeUnit: string
{
    use HasChoices;

    case MINUTES = 'minutes';
    case HOURS = 'hours';
    case BUSINESS_DAYS = 'business_days';
    case DAYS = 'days';
    case WEEKS = 'weeks';
    case MONTHS = 'months';
    case YEARS = 'years';
}
