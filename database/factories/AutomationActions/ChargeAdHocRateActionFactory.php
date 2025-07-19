<?php

namespace Database\Factories\AutomationActions;

use App\Models\AutomationAction;
use App\Models\Automations\PurchaseOrderAutomation;
use App\Models\BillingRate;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChargeAdHocRateActionFactory extends Factory
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
            'position' => 1,
            'minimum' => 1.0,
            'tolerance' => 5,
            'threshold' => 0.0,
            'billing_rate_id' =>  fn () => BillingRate::factory()->create()->id
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (AutomationAction &$action) {
            $action->position = AutomationAction::where('automation_id', $action->automation_id)->count() + 1;

            if ($action->billingRate->type != BillingRate::AD_HOC) {
                $action->billingRate->type = BillingRate::AD_HOC;
                $action->billingRate->save();
            }
        });
    }
}
