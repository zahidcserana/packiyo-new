<?php

namespace Database\Factories;

use App\Models\BillingRate;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\RateCard;

class BillingRateFactory extends Factory
{
    public function definition()
    {
        $name = $this->faker->sentence($this->faker->numberBetween(1, 6));
        $code = strtolower(str_replace(' ', '-', trim($name)));
        $billingRateTypes = array_keys(BillingRate::BILLING_RATE_TYPES);

        return [
            'is_enabled' => true,
            'name' => $name,
            'rate_card_id' => fn () => RateCard::factory()->create()->id,
            'type' => $this->faker->randomElement($billingRateTypes),
            'settings' => [],  // TODO: fake this - it's specific to each type.
            'code' => $code,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
