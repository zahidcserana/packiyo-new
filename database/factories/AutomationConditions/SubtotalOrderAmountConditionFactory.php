<?php

namespace Database\Factories\AutomationConditions;

use App\Models\AutomationCondition;
use App\Models\Automations\OrderAutomation;
use App\Models\Automations\NumberComparison;
use App\Models\Automations\OrderNumberField;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubtotalOrderAmountConditionFactory extends Factory
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
            'field_name' => OrderNumberField::SUBTOTAL,
            'comparison_operator' => $this->faker->randomElement(NumberComparison::cases()),
            'number_field_value' => 1
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
