<?php

namespace Database\Factories;

use App\Models\ContactInformation;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition()
    {
        return [
            'number' => $this->faker->unique()->randomNumber(7),
            'priority' => $this->faker->boolean(),
            'created_at' => now(),
            'updated_at' => now(),
            'ordered_at' => now(),
            'order_status_id' => function (array $attributes) {
                $orderStatuses = OrderStatus::all();

                return $orderStatuses->count() > 0 ?
                    $orderStatuses->random()->id :
                    OrderStatus::factory()->create(['customer_id' => $attributes['customer_id']])->id;
            },
            'shipping_contact_information_id' => fn () => ContactInformation::factory()->create()->id,
            'billing_contact_information_id' => fn () => ContactInformation::factory()->create()->id,
            'customer_id' => function () {
                $customers = Customer::all();

                return $customers->count() > 10 ?
                    $customers->random()->id :
                    Customer::factory()->create()->id;
            }
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        $updateOrderStatus = function (Order &$order) {
            if ($order->customer->id != $order->orderStatus->customer->id) {
                $order->orderStatus->customer()->associate($order->customer);
            }
        };

        return $this->afterMaking(function (Order $order) use (&$updateOrderStatus) {
            $updateOrderStatus($order);
        })->afterCreating(function (Order $order) use (&$updateOrderStatus) {
            $updateOrderStatus($order);
            $order->save();
        });
    }
}
