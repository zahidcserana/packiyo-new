<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\AutomationActions\ChargeAdHocRateAction;
use App\Models\Product;

trait AddsChargeAdHocRateAction
{
    use AppliesToSingle;

    protected function addChargeAdHocRateAction(AutomationChoices $automationChoices): ChargeAdHocRateAction
    {
        $customer = $this::getCustomerFromChoices($automationChoices);
        $sku = $this->anticipate(
            __('Which SKU should be added?'),
            fn (string $input) => strlen($input) < 2 ? []
                : $customer->products()->where('sku', 'like', $input . '%')->limit(10)->pluck('sku')->toArray()
        );
        $quantity = (float) $this->ask(__('How many should be added?'));
        $product = Product::where(['customer_id' => $customer->id, 'sku' => $sku])
            ->firstOrFail();

        $action = new ChargeAdHocRateAction(['quantity' => $quantity]);
        $action->product()->associate($product);

        return $action;
    }
}
