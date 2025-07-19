<?php

namespace Database\Factories\AutomationActions;

use App\Models\AutomationAction;
use App\Models\Automations\AppliesToLineItems;
use App\Models\Automations\PurchaseOrderAutomation;
use App\Models\ShippingBox;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChargeShippingBoxRateActionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'automation_id' => fn () => PurchaseOrderAutomation::factory()->create()->id,
            'shipping_box_id' =>  fn () => ShippingBox::factory()->create()->id,
            'applies_to' => AppliesToLineItems::ALL,
            'amount' => 0.0
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
