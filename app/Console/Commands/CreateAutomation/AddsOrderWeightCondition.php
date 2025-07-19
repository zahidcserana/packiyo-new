<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\Automations\NumberComparison;
use App\Models\Automations\WeightUnit;
use App\Models\AutomationConditions\OrderWeightCondition;

trait AddsOrderWeightCondition
{
    protected function addOrderWeightCondition(AutomationChoices $automationChoices): OrderWeightCondition
    {
        $operator = $this->choice(
            __('How should the weight be compared?'),
            collect(NumberComparison::cases())->pluck('name')->toArray()
        );
        $operator = collect(NumberComparison::cases())->first(fn (NumberComparison $enum) => $enum->name == $operator);
        $value = (float) $this->ask(__('What is the number to compare the weight to?'));
        $unit = $this->choice(
            __('What is the unit of measure of your number?'),
            collect(WeightUnit::cases())->pluck('name')->toArray()
        );
        $unit = collect(WeightUnit::cases())->first(fn (WeightUnit $enum) => $enum->name == $unit);

        return new OrderWeightCondition([
            'comparison_operator' => $operator,
            'number_field_value' => $value,
            'unit_of_measure' => $unit
        ]);
    }
}
