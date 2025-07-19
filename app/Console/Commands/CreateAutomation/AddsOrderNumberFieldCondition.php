<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\Automations\NumberComparison;
use App\Models\Automations\OrderNumberField;
use App\Models\AutomationConditions\OrderNumberFieldCondition;

trait AddsOrderNumberFieldCondition
{
    protected function addOrderNumberFieldCondition(AutomationChoices $automationChoices): OrderNumberFieldCondition
    {
        $fieldName = $this->choice(
            __('Which field should be evaluated?'),
            collect(OrderNumberField::cases())->pluck('value')->toArray()
        );
        $operator = $this->choice(
            __('How should the field be compared?'),
            collect(NumberComparison::cases())->pluck('value')->toArray()
        );
        $value = (float) $this->ask(__('What is the number to compare the field to?'));

        return new OrderNumberFieldCondition([
            'field_name' => $fieldName,
            'comparison_operator' => $operator,
            'number_field_value' => $value
        ]);
    }
}
