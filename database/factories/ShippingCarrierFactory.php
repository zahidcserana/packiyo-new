<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShippingCarrierFactory extends Factory
{
    public function definition()
    {
        $carrierNames = ['UPS', 'FedEx', 'DHL', 'USPS', 'Royal Mail'];

        return [
            'customer_id' => function () {
                return Customer::factory()->create()->id;
            },
            'name' => $this->faker->randomElement($carrierNames),
            'carrier_service' => 'easypost', // Add more.
            'credential_type' => 'App\Models\EasypostCredential', // Add more.
            'credential_id' => $this->faker->randomNumber(9),
            'settings' => [],  // Specific to the carrier service.
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
