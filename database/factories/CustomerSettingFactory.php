<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerSettingFactory extends Factory
{
    public function definition()
    {
        return [
            'customer_id' => fn () => Customer::factory()->create()->id,
            'key' => $this->faker->randomElement(CustomerSetting::CUSTOMER_SETTING_KEYS),
            'value' => null,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
