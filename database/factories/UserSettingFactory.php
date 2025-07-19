<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserSettingFactory extends Factory
{
    public function definition()
    {
        return [
            'user_id' => fn () => User::factory()->create()->id,
            'key' => $this->faker->randomElement(UserSetting::USER_SETTING_KEYS),
            'value' => null,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
