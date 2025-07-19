<?php

namespace Database\Factories\AutomationActions;

use App\Models\AutomationAction;
use App\Models\Automations\OrderAutomation;
use App\Models\Automations\OrderTextField;
use Illuminate\Database\Eloquent\Factories\Factory;

class SetDateFieldActionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'automation_id' => fn () => OrderAutomation::factory()->create()->id,
            'position' => 1,
            'field_name' => $this->faker->randomElement(OrderTextField::cases()),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (AutomationAction &$action) {
            $action->position = AutomationAction::where('automation_id', $action->automation_id)->count() + 1;
        });
    }
}
