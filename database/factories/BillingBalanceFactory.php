<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class BillingBalanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $threePL = Customer::factory()->create();

        return [
            'threepl_id' => fn () => $threePL->id,
            'warehouse_id' => fn () => Warehouse::factory()->create(['customer_id' => $threePL->id])->id,
            'client_id' => fn () => Customer::factory()->create(['parent_id' => $threePL->id])->id,
            'amount' => $this->faker->randomFloat(2, 1.0, 1000.0),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
