<?php

namespace App\Rules\Location;

use App\Models\Location;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\ValidationException;
use LogicException;

class Unprotected implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (is_null($value)) {
            throw new LogicException('The ID must be under validation data to check if the location is protected.');
        }

        $location = Location::findOrFail($value, ['id', 'protected']);

        if ($location->protected) {
            // We throw it as a validation exception instead of returning false
            // to be able to show the modal in the frontend (Locations table)
            throw ValidationException::withMessages([
                __('This location is protected, so it cannot be deleted.')
            ])->status(403);
        }

        return true;
    }

    public function message(): string
    {
        return 'Location is protected and cannot be deleted.';
    }
}
