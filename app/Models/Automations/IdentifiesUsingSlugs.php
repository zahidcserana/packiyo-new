<?php

namespace App\Models\Automations;

use Illuminate\Support\Str;

trait IdentifiesUsingSlugs
{
    public static function classToSlug(string $class): string
    {
        return Str::slug(class_basename($class));
    }
}
