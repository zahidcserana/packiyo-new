<?php

namespace Database\Factories\AutomationConditions;

use App\Models\AutomationCondition;
use App\Models\Automations\OrderAutomation;
use App\Models\OrderChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderChannelConditionFactory extends Factory
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
            'order_channel_id' => fn () => OrderChannel::factory()->create()->id
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
