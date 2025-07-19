<?php

namespace Database\Factories\AutomationConditions;

use App\Models\AutomationCondition;
use App\Models\Automations\AppliesToItemsTags;
use App\Models\Automations\OrderAutomation;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemTagsConditionFactory extends Factory
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
            'applies_to' => AppliesToItemsTags::SOME
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (AutomationCondition &$condition) {
            $condition->position = AutomationCondition::where('automation_id', $condition->automation_id)->count() + 1;
        })->afterCreating(function (AutomationCondition &$condition) {
            if (!$condition->tags->count()) {
                $condition->tags()->saveMany(Tag::factory()->count($this->faker->numberBetween(1, 12))->make());
            }
        });
    }
}
