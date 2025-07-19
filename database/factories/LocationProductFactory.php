<?php

namespace Database\Factories;

use App\Models\{Location, Product};
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationProductFactory extends Factory
{
    public function definition()
    {
        return [
            'product_id' => Product::factory(), // Apparently this works.
            'location_id' => Location::factory(), // Apparently this works.
            'quantity_on_hand' => $this->faker->numberBetween(0, 100),
            'quantity_reserved_for_picking' => 0
        ];
    }
}
