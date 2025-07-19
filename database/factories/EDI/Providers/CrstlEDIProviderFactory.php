<?php

namespace Database\Factories\EDI\Providers;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CrstlEDIProviderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'customer_id' => fn () => Customer::factory()->create()->id,
            'access_token' => $this->faker->word,
            'refresh_token' => $this->faker->word,
            'access_token_expires_at' => now()->addHours(2),
            'is_multi_crstl_org' => false,
            'external_role' => 'Admin',
            'external_organization_id' => 'ad320619-bc2b-43a3-88b9-ab2a532e32be',
            'is_sandbox' => false,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    public function sandboxed(): CrstlEDIProviderFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_sandbox' => true
            ];
        });
    }
}
