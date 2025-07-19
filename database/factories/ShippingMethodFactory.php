<?php

namespace Database\Factories;

use App\Models\ShippingCarrier;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShippingMethodFactory extends Factory
{
    public function definition()
    {
        $methodNames = ['Ground', 'Air', 'Express'];

        return [
            'shipping_carrier_id' => function () { return ShippingCarrier::factory()->create()->id; },
            'name' => $this->faker->randomElement($methodNames),
            'settings' => [],
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
