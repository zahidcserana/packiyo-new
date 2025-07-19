<?php

namespace App\Rules;

use App\Features\LotTrackingConstraints;
use App\Models\Product;
use Illuminate\Contracts\Validation\Rule;
use Laravel\Pennant\Feature;

class CanChangeLotTracking implements Rule
{
    private $message = '';

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(
        private Product $product
    )
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (Feature::for('instance')->inactive(LotTrackingConstraints::class)) {
            return true;
        }

        if ($this->product->lot_tracking != $value) {
            if ($value && $this->product->locations()->count() > 0) {
                $this->message = __('Cannot enable lot tracking - product already has inventory. Remove inventory first');

                return false;
            } else if (!$value && $this->product->placedLotItems->count() > 0) {
                $this->message = __('Cannot disable lot tracking - inventory already has lot information. Remove inventory first');

                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
