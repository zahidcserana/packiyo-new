<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\Automations\AppliesToLineItems;
use App\Models\Automations\NumberComparison;
use App\Models\AutomationConditions\OrderLineItemCondition;
use App\Models\Product;

trait AddsOrderLineItemCondition
{
    use AppliesToSingle;

    protected function addOrderLineItemCondition(AutomationChoices $automationChoices): OrderLineItemCondition|array
    {
        $customer = null;
        $appliesTo = $this->choice(
            __('Which order items should this apply to?'),
            collect(AppliesToLineItems::cases())->pluck('value')->toArray()
        );

        if ($appliesTo == AppliesToLineItems::SOME->value) {
            $customer = $this::getCustomerFromChoices($automationChoices);
            $skus = array_map('trim', str_getcsv($this->anticipate(
                __('Which SKUs should be checked? (Separate multiple with commas.)'),
                fn (string $input) => strlen($input) < 2 ? []
                    : Product::where('customer_id', $customer->id)
                        ->where('sku', 'like', $input . '%')
                        ->limit(10)
                        ->pluck('sku')
                        ->toArray()
            )));
        }

        $operator = $this->choice(
            __('How should the sum of the matching line items quantities be compared?'),
            collect(NumberComparison::cases())->pluck('name')->toArray()
        );
        $operator = collect(NumberComparison::cases())
            ->first(fn (NumberComparison $enum) => $enum->name == $operator);
        $value = (float) $this->ask(__('What is the number to compare the sum to?'));

        $action = new OrderLineItemCondition([
            'applies_to' => $appliesTo,
            'number_field_value' => $value,
            'comparison_operator' => $operator
        ]);

        if ($action->applies_to == AppliesToLineItems::SOME) {
            $products = $customer->products()->whereIn('sku', $skus)->get();
            $callback = fn (OrderLineItemCondition $action)
                => $action->matchesProducts()->attach($products->pluck('id')->toArray());

            return [$action, $callback];
        }

        return $action;
    }
}
