<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Customer;

class ShippingBoxFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'customer_id' => Customer::factory()->create()->id,
            'name' => $this->faker->name(),
            'length' => $this->faker->randomFloat(2, 0.1, 48),
            'width' => $this->faker->randomFloat(2, 0.1, 48),
            'height' => $this->faker->randomFloat(2, 0.1, 48),
            'height_locked' => false,
            'length_locked' => false,
            'width_locked' => false,
            'cost' => null,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
