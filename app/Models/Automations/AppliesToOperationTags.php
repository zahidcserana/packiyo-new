<?php

namespace App\Models\Automations;

/**
 * Which operation tags should be matched.
 */
enum AppliesToOperationTags: string
{
    case ALL = 'all';
    case SOME = 'some';
    case NONE = 'none';
}
