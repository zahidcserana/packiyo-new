<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition()
    {
        return [
            'customer_id' => function () {
                return Customer::all()->random()->id;
            },
            'sku' => $this->faker->randomNumber(5),
            'name' => $this->faker->unique()->name,
            'price' => $this->faker->randomNumber(2),
            'notes' => $this->faker->domainName,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
