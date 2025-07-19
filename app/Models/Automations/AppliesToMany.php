<?php

namespace App\Models\Automations;

trait AppliesToMany
{
    public static function appliesToMany(): bool
    {
        return true;
    }
}
