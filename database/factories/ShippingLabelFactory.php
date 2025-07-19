<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Shipment;
use App\Models\Customer;
use App\Models\User;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShippingLabelFactory extends Factory
{
    public function definition()
    {
        $processingStatuses = [
            Shipment::PROCESSING_STATUS_PENDING,
            Shipment::PROCESSING_STATUS_IN_PROGRESS,
            Shipment::PROCESSING_STATUS_SUCCESS,
            Shipment::PROCESSING_STATUS_FAILED
        ];

        return [
            'order_id' => function() {
                return Order::factory()->create()->id;
            },
            'shipping_method_id' => function() {
                return ShippingMethod::factory()->create()->id;
            },
            'user_id' => function () {
                return User::factory()->create()->id;
            },
            'processing_status' => $this->faker->randomElement($processingStatuses),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
