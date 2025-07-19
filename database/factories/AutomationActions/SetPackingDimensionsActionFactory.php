<?php

namespace Database\Factories\AutomationActions;

use App\Models\AutomationAction;
use App\Models\Automations\OrderAutomation;
use App\Models\ShippingBox;
use Illuminate\Database\Eloquent\Factories\Factory;

class SetPackingDimensionsActionFactory extends Factory
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
            'shipping_box_id' => fn () => ShippingBox::factory()->create()->id
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
