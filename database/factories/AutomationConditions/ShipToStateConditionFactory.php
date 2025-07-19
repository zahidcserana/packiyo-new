<?php

namespace Database\Factories\AutomationConditions;

use App\Models\AutomationCondition;
use App\Models\Automations\OrderAutomation;
use App\Models\Automations\TextComparison;
use App\Models\Automations\OrderTextField;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipToStateConditionFactory extends Factory
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
            'position' => 1,
            'field_name' => OrderTextField::SHIPPING_STATE,
            'comparison_operator' => $this->faker->randomElement(TextComparison::cases()),
            'text_field_values' => ['CA', 'NY'],
            'case_sensitive' => $this->faker->boolean()
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (AutomationCondition &$condition) {
            $condition->position = AutomationCondition::where('automation_id', $condition->automation_id)->count() + 1;
        });
    }
}
