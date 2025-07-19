<?php

namespace App\Models\Automations;

/**
 * Weight units for automations.
 */
enum WeightUnit: string
{
    case POUNDS = 'lb';
    case OUNCES = 'oz';
    case KILOGRAMS = 'kg';
    case GRAMS = 'g';
    case LITRES = 'l';

    public static function getReadableText($enum): string
    {
        return match ($enum) {
            self::POUNDS => 'pounds',
            self::OUNCES => 'ounces',
            self::KILOGRAMS => 'kilograms',
            self::GRAMS => 'grams',
            self::LITRES => 'litres',
            default => '',
        };
    }
}
