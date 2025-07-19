<?php

namespace App\Models\Automations;

trait AppliesToSingle
{
    public static function appliesToMany(): bool
    {
        return false;
    }
}
