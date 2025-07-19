<?php

namespace Database\Factories\BillingCharges;

use App\Models\Automations\OrderAutomation;
use App\Models\BillingBalance;
use App\Models\BillingCharges\ShippingBoxCharge;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShippingBoxChargeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'billing_balance_id' => fn () => BillingBalance::factory()->create()->id,
            'description' => 'This is a shipping box charge.',
            'quantity' => 1.0,
            'amount' => 1.50,
            'automation_id' => fn () => OrderAutomation::factory()->create()->id,
            'shipment_id' => fn () => Shipment::factory()->create()->id,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (ShippingBoxCharge &$charge) {
            $charge->shipment->created_at = $charge->created_at;
            $charge->shipment->updated_at = $charge->updated_at;
            $charge->shipment->save();
        });
    }
}
