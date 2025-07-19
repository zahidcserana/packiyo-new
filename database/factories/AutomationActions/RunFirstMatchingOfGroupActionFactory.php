<?php

namespace Database\Factories\AutomationActions;

use App\Models\AutomationAction;
use App\Models\Automations\OrderAutomation;
use Illuminate\Database\Eloquent\Factories\Factory;

class RunFirstMatchingOfGroupActionFactory extends Factory
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
            'position' => 1
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (AutomationAction &$action) {
            $action->position = AutomationAction::where('automation_id', $action->automation_id)->count() + 1;
        })->afterCreating(function (AutomationAction &$action) {
            if (!$action->automations->count()) {
                $action->automations()->saveMany(OrderAutomation::factory()->count($this->faker->numberBetween(1, 12))->make());
            }
        });
    }
}
