<?php

namespace Database\Factories\Automations;

use App\Models\Automation;
use App\Models\Automations\AppliesToCustomers;
use App\Models\Automations\OrderAutomation;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use LogicException;

class OrderAutomationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->sentence($this->faker->numberBetween(1, 6));

        return [
            'customer_id' => fn () => Customer::factory()->create()->id,
            'name' => $name,
            'is_enabled' => true,
            'position' => 1,
            'target_events' => [$this->faker->randomElement(OrderAutomation::getSupportedEvents())],
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Automation &$automation) {
            if (is_null($automation->applies_to)) {
                if ($automation->customer->isStandalone()) {
                    $automation->applies_to = AppliesToCustomers::OWNER;
                } elseif ($automation->customer->is3pl()) {
                    $automation->applies_to = AppliesToCustomers::ALL;
                } else {
                    throw new LogicException('Automations cannot be owned by 3PL clients.');
                }
            }

            $automation->position = Automation::where('customer_id', $automation->customer_id)->count() + 1;
        })->afterCreating(function (Automation &$automation) {
            if (is_null($automation->original_revision_automation_id)) {
                $automation->original_revision_automation_id = $automation->id; // associate() won't work.
                $automation->save();
                $automation->revisions()->attach($automation->id);
            }
        });
    }
}
