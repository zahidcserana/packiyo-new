<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Shipment;
use App\Models\Customer;
use App\Models\ShipmentTracking;
use App\Models\User;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentTrackingFactory extends Factory
{
    public function definition()
    {
        $trackingTypes = [
            ShipmentTracking::TYPE_SHIPPING,
            ShipmentTracking::TYPE_RETURN
        ];

        return [
            'shipment_id' => function () {
                return Shipment::factory()->create()->id;
            },
            'tracking_number' => (string) $this->faker->randomNumber(7) . (string) $this->faker->randomNumber(7),
            'tracking_url' => $this->faker->url,
            'type' => $this->faker->randomElement($trackingTypes),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
