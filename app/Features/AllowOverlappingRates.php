<?php

namespace App\Features;

class AllowOverlappingRates
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(mixed $scope): bool
    {
        return false;
    }
}
