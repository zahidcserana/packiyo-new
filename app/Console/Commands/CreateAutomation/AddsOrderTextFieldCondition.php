<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\Automations\Incoterms;
use App\Models\Automations\TextComparison;
use App\Models\Automations\OrderTextField;
use App\Models\Automations\RegionCode;
use App\Models\AutomationConditions\OrderTextFieldCondition;

trait AddsOrderTextFieldCondition
{
    protected function addOrderTextFieldCondition(AutomationChoices $automationChoices): OrderTextFieldCondition
    {
        $fieldName = $this->choice(
            __('Which field should be evaluated?'),
            collect(OrderTextField::readable())->pluck('value')->toArray()
        );
        $choices = [];

        if ($fieldName == OrderTextField::INCOTERMS->value) {
            $choices = collect(Incoterms::cases())->pluck('value')->toArray();
        } elseif (in_array($fieldName, [
            OrderTextField::SHIPPING_CONTINENT_CODE->value, OrderTextField::BILLING_CONTINENT_CODE->value
        ])) {
            $choices = collect(RegionCode::cases())->pluck('value')->toArray();
        }

        $operator = $this->choice(
            __('How should the field be compared?'),
            collect(TextComparison::cases())->pluck('value')->toArray()
        );
        $fieldValues = array_map('trim', str_getcsv($this->anticipate(__(
            'Which values should the field be compared to? '
            . '(Separate multiple with commas, enclose with double quotes if needed.)',
        ), $choices)));
        $caseSensitive = $this->confirm(__('Should the comparison be case-sensitive?'), true);

        return new OrderTextFieldCondition([
            'field_name' => $fieldName,
            'text_field_values' => $fieldValues,
            'comparison_operator' => $operator,
            'case_sensitive' => $caseSensitive
        ]);
    }
}
