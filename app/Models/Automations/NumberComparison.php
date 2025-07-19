<?php

namespace App\Models\Automations;

/**
 * Operators to compare numeric amounts.
 */
enum NumberComparison: string
{
    case EQUAL = '==';
    case NOT_EQUAL = '!=';
    case LESSER = '<';
    case GREATER = '>';
    case LESSER_OR_EQUAL = '<=';
    case GREATER_OR_EQUAL = '>=';

    public static function getReadableText($enum): string
    {
        return match ($enum) {
            self::EQUAL => 'is',
            self::NOT_EQUAL => 'is not',
            self::LESSER => 'is less than',
            self::GREATER => 'is more than',
            self::LESSER_OR_EQUAL => 'is equal or more than',
            self::GREATER_OR_EQUAL => 'is equal or less than',
            default => '',
        };
    }
}
