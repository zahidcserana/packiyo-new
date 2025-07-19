<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingBox;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageFactory extends Factory
{
    public function definition()
    {
        return [
            'order_id' => function () {
                return Order::factory()->create()->id;
            },
            'shipping_box_id' => function () {
                return ShippingBox::factory()->create()->id;
            },
            'weight' => $this->faker->randomFloat(2, 0.1, 48),
            'length' => $this->faker->randomFloat(2, 0.1, 48),
            'width' => $this->faker->randomFloat(2, 0.1, 48),
            'height' => $this->faker->randomFloat(2, 0.1, 48),
            'shipment_id' => function () {
                return Shipment::factory()->create()->id;
            },
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
