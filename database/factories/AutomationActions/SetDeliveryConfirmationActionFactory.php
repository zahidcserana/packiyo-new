<?php

namespace Database\Factories\AutomationActions;

use App\Models\AutomationAction;
use App\Models\Automations\OrderAutomation;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class SetDeliveryConfirmationActionFactory extends Factory
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
            'text_field_value' => $this->faker->randomElement([
                Order::DELIVERY_CONFIRMATION_SIGNATURE,
                Order::DELIVERY_CONFIRMATION_NO_SIGNATURE,
                Order::DELIVERY_CONFIRMATION_ADULT_SIGNATURE
            ])
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
