<?php

namespace App\Features;

class RequiredReadyToPickForPacking
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(mixed $scope): bool
    {
        return true;
    }
}
