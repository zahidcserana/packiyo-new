<?php

namespace App\Models\Automations;

use App\Models\Automations\NumberComparison;

trait ComparesNumbers
{
    protected function compare(int|float $operationValue, NumberComparison $operator, int|float $conditionValue): bool
    {
        if ($operator == NumberComparison::EQUAL) {
            $matches = $operationValue == $conditionValue;
        } elseif ($operator == NumberComparison::LESSER) {
            $matches = $operationValue < $conditionValue;
        } elseif ($operator == NumberComparison::GREATER) {
            $matches = $operationValue > $conditionValue;
        } elseif ($operator == NumberComparison::LESSER_OR_EQUAL) {
            $matches = $operationValue <= $conditionValue;
        } elseif ($operator == NumberComparison::GREATER_OR_EQUAL) {
            $matches = $operationValue >= $conditionValue;
        }

        return $matches;
    }
}
