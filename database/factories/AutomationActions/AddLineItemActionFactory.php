<?php

namespace Database\Factories\AutomationActions;

use App\Models\Automation;
use App\Models\AutomationAction;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddLineItemActionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'automation_id' => fn () => Automation::factory()->create()->id,
            'position' => 1,
            'product_id' => fn () => Product::factory()->create()->id,
            'quantity' => $this->faker->randomNumber()
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
