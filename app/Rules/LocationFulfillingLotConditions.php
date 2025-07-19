<?php

namespace App\Rules;

use App\Features\LotTrackingConstraints;
use App\Models\Location;
use App\Models\Lot;
use App\Models\Product;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Pennant\Feature;

class LocationFulfillingLotConditions implements Rule
{
    public function __construct(
        private Product $product,
        private ?Lot $lot = null,
        private ?Location $fromLocation = null
    )
    {
    }

    public function passes($attribute, $value)
    {
        if (Feature::for('instance')->inactive(LotTrackingConstraints::class)) {
            return true;
        }

        if ($this->product->lot_tracking) {
            if (!$this->lot && $this->fromLocation) {
                $this->lot = $this->product->lots()
                    ->whereHas(
                        'placedLotItems',
                        fn (Builder $query) => $query->where('location_id', $this->fromLocation->id))
                    ->first();
            }

            if (!$this->lot) {
                return false;
            }
        } else {
            // product already placed on the location - we should allow updating the inventory on it
            if ($this->product->locationProducts()->where('location_id', $value)->exists()) {
                return true;
            }
        }

        $passes = Location::where('id', $value)
                ->whereProductCanBeAdded($this->product, $this->lot)
                ->exists();

        return $passes;
    }

    public function message()
    {
        return __('The location cannot be used');
    }
}
