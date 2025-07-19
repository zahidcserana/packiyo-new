<?php

namespace App\Models\Automations;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class SetDateFieldActionConfigurationCast implements CastsInboundAttributes
{
    /**
     * Prepare the given value for storage.
     *
     * @param  Model  $model
     * @param  string  $key
     * @param  SetDateFieldActionConfiguration $value
     * @param  array  $attributes
     * @return string|null
     */
    public function set($model, $key, $value, $attributes): string|null
    {
        if (is_null($value)) {
            return null;
        }

        if (! $value instanceof SetDateFieldActionConfiguration) {
            throw new InvalidArgumentException('The given value is not an instance of SetDateFieldConfiguration');
        }

        return json_encode($value->toArray());
    }
}
