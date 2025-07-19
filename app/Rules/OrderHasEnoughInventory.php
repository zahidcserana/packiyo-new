<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class OrderHasEnoughInventory implements Rule
{
    private $notFulfillablePackingStateLocations = [];

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
         $this->notFulfillablePackingStateLocations = app('shipping')->notFulfillablePackingStateLocations($value);

         return empty($this->notFulfillablePackingStateLocations);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        $locationsString = '';

        foreach ($this->notFulfillablePackingStateLocations as $sku => $locations) {
            $locationsString = $sku . ': ' . implode(', ', $locations);
        }

        return __('Not enough inventory on locations: :locations', [
            'locations' => $locationsString
        ]);
    }
}
