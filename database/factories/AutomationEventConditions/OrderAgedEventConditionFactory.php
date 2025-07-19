<?php

namespace Database\Factories\AutomationEventConditions;

use App\Models\Automations\OrderAutomation;
use App\Models\Automations\TimeUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderAgedEventConditionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'automation_id' => fn () => OrderAutomation::factory()->create()->id,
            'number_field_value' => $this->faker->numberBetween(1, 30),
            'unit_of_measure' => $this->faker->randomElement(TimeUnit::cases()),
            'pending_only' => $this->faker->boolean(),
            'ignore_holds' => $this->faker->boolean()
        ];
    }
}
