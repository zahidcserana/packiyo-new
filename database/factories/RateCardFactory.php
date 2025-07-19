<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Customer;

class RateCardFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            '3pl_id' => function () {
                return Customer::factory()->create()->id;
            },
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
