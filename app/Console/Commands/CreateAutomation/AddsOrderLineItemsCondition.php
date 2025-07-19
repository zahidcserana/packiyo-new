<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\Automations\AppliesToItemsQuantity;
use App\Models\Automations\NumberComparison;
use App\Models\AutomationConditions\OrderLineItemsCondition;

trait AddsOrderLineItemsCondition
{
    use AppliesToSingle;

    protected function addOrderLineItemsCondition(AutomationChoices $automationChoices): OrderLineItemsCondition|array
    {
        $appliesTo = $this->choice(
            __('Which order item quantities should this apply to?'),
            collect(AppliesToItemsQuantity::cases())->pluck('value')->toArray()
        );

        $operator = $this->choice(
            __('How should the quantities of the line items be compared?'),
            collect(NumberComparison::cases())->pluck('name')->toArray()
        );
        $operator = collect(NumberComparison::cases())
            ->first(fn (NumberComparison $enum) => $enum->name == $operator);
        $value = (float) $this->ask(__('What is the number to compare the sum to?'));

        $action = new OrderLineItemsCondition([
            'applies_to' => $appliesTo,
            'number_field_value' => $value,
            'comparison_operator' => $operator
        ]);

        return $action;
    }
}
