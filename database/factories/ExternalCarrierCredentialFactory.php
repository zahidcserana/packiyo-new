<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExternalCarrierCredentialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'reference' => $this->faker->word,
            'create_shipment_label_url' => null,
            'create_return_label_url' => null,
            'void_label_url' => null,
            'customer_id' => function () {
                $customers = Customer::all();

                return $customers->count() > 10 ?
                    $customers->random()->id :
                    Customer::factory()->create()->id;
            },
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
