<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderChannelFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'customer_id' => fn () => Customer::factory()->create()->id,
            'settings' => [],
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
