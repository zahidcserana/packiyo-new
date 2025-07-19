<?php

namespace App\Models\Automations;

/**
 * Operators to match text patterns.
 */
enum PatternComparison: string
{
    case MATCHES = 'matches';
    case NOT_MATCHES = 'not_matches';
    case STARTS_WITH_MATCH = 'starts_with_match';
    case NOT_STARTS_WITH_MATCH = 'not_starts_with_match';
    case ENDS_WITH_MATCH = 'ends_with_match';
    case NOT_ENDS_WITH_MATCH = 'not_ends_with_match';
    case CONTAINS_MATCH = 'contains_match';
    case NOT_CONTAINS_MATCH = 'not_contains_match';

    public static function isAnyNot(PatternComparison $comparison): bool
    {
        return in_array($comparison, [
            static::NOT_MATCHES,
            static::NOT_STARTS_WITH_MATCH,
            static::NOT_ENDS_WITH_MATCH,
            static::NOT_CONTAINS_MATCH
        ]);
    }

    public static function isAnyMatches(PatternComparison $comparison): bool
    {
        return in_array($comparison, [static::MATCHES, static::NOT_MATCHES]);
    }

    public static function isAnyStartsWith(PatternComparison $comparison): bool
    {
        return in_array($comparison, [static::STARTS_WITH_MATCH, static::NOT_STARTS_WITH_MATCH]);
    }

    public static function isAnyEndsWith(PatternComparison $comparison): bool
    {
        return in_array($comparison, [static::ENDS_WITH_MATCH, static::NOT_ENDS_WITH_MATCH]);
    }
}
