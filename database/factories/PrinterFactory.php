<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrinterFactory extends Factory
{
    public function definition()
    {
        return [
            'hostname' => $this->faker->name(),
            'name' => $this->faker->name(),
            'user_id' => User::factory(),
            'customer_id' => Customer::factory(),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
