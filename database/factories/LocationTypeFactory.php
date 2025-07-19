<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationTypeFactory extends Factory
{
    public function definition()
    {
        return [
            'customer_id' => function() {
                return Customer::all()->random()->id;
            },
            'name' => $this->faker->word,
            'pickable' => $this->faker->numberBetween(0, 1),
            'sellable' => $this->faker->numberBetween(0, 1),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
