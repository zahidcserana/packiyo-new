<?php

namespace App\Traits;

trait HasUniqueIdentifierSuggestionTrait
{
    public static function getUniqueIdentifier($prefix, $referenceValue): string
    {
        $modelClass = get_called_class();

        $identifierNumber = $modelClass
            ::withTrashed()
            ->where(static::$uniqueIdentifierReferenceColumn, $referenceValue)
            ->where(static::$uniqueIdentifierColumn, 'like', $prefix . '%')
            ->count();

        do {
            $identifier = $prefix . ++$identifierNumber;
        } while ($modelClass
            ::where(static::$uniqueIdentifierReferenceColumn, $referenceValue)
            ->where(static::$uniqueIdentifierColumn, $identifier)
            ->exists());

        return $identifier;
    }
}
