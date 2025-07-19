<?php

namespace Database\Factories\AutomationConditions;

use App\Models\AutomationCondition;
use App\Models\Automations\OrderAutomation;
use App\Models\Automations\OrderTextField;
use App\Models\Automations\PatternComparison;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderTextPatternConditionFactory extends Factory
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
            'field_name' => $this->faker->randomElement(OrderTextField::cases()),
            'comparison_operator' => $this->faker->randomElement(PatternComparison::cases()),
            'text_pattern' => '.*'
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
