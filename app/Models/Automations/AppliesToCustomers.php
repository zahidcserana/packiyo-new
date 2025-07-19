<?php

namespace App\Models\Automations;

/**
 * Clients the automation applies to.
 */
enum AppliesToCustomers: string
{
    case OWNER = 'owner';
    case ALL = 'all';
    case SOME = 'some';
    case NOT_SOME = 'not_some';

    public static function isNotAll(AppliesToCustomers $appliesTo): bool
    {
        return in_array($appliesTo, [static::SOME, static::NOT_SOME]);
    }
}
