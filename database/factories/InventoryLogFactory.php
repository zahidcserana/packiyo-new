<?php

namespace Database\Factories;

use App\Models\{User, Product, Location};
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryLogFactory extends Factory
{
    public function definition()
    {
        $quantity = function () {
            return $this->faker->randomNumber();
        };

        return [
            'user_id' => function () {
                return User::factory()->create()->id;
            },
            'product_id' => function () {
                return Product::factory()->create()->id;
            },
            'location_id' => function () {
                return Location::factory()->create()->id;
            },
            'previous_on_hand' => 0,
            'new_on_hand' => $quantity,
            'quantity' => $quantity,
            'reason' => $this->faker->sentence,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
