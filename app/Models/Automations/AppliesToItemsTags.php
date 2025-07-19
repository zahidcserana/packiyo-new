<?php

namespace App\Models\Automations;

/**
 * Which line items should match the tags.
 */
enum AppliesToItemsTags: string
{
    case ALL = 'all';
    case SOME = 'some';
    case NONE = 'none';
}
