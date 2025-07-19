<?php

namespace App\Models\Automations;

/**
 * Operators to compare numeric amounts.
 */
enum TextComparison: string
{
    case SOME_EQUALS = 'some_equals';
    case NONE_EQUALS = 'none_equals';
    case SOME_STARTS_WITH = 'some_starts_with';
    case NONE_STARTS_WITH = 'none_starts_with';
    case SOME_ENDS_WITH = 'some_ends_with';
    case NONE_ENDS_WITH = 'none_ends_with';
    case SOME_CONTAINS = 'some_contains';
    case NONE_CONTAINS = 'none_contains';

    public static function getReadableText($enum, $multiple = false): string
    {
        return match ($enum) {
            self::SOME_EQUALS => ($multiple ? 'is in list' : 'is'),
            self::NONE_EQUALS => ($multiple ? 'is not in' : 'is'),
            self::SOME_STARTS_WITH => 'starts with',
            self::NONE_STARTS_WITH => 'not starts with',
            self::SOME_ENDS_WITH => 'ends with',
            self::NONE_ENDS_WITH => 'not ends with',
            self::SOME_CONTAINS => 'contains',
            self::NONE_CONTAINS => 'does not contain',
            default => '',
        };
    }
}
