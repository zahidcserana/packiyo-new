<?php

namespace App\Features;

use Illuminate\Support\Lottery;

class PreventDuplicateBarcodes
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(mixed $scope): bool
    {
        return false;
    }
}
