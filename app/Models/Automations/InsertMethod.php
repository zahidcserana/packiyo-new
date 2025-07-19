<?php

namespace App\Models\Automations;

/**
 * Insert methods to alter text fields in automations.
 */
enum InsertMethod: string
{
    case REPLACE = 'replace';
    case PREPEND = 'prepend';
    case APPEND = 'append';
}
