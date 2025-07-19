<?php

namespace App\Traits;

trait TranslatableEnumTrait
{
    public static function translatedValues(): array
    {
        $translatedValues = [];

        foreach (self::cases() as $case) {
            $translatedValues[$case->value] = __($case->name);
        }

        return $translatedValues;
    }
}
