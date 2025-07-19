<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\AutomationActions\AddLineItemAction;
use App\Models\Product;

trait AddsAddLineItemAction
{
    use AppliesToSingle;

    protected function addAddLineItemAction(AutomationChoices $automationChoices): AddLineItemAction
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

        $force = $this->choice(
            __('Should the exact quantity be added regardless of existing line items for the same SKU?'),
            [__('yes'), __('no')], // Choices.
            __('no') // Default.
        );

        $ignoreCancelled = $this->confirm(__('Should cancelled orders be ignored?'), true);
        $ignoreFulfilled = $this->confirm(__('Should fulfilled orders be ignored?'), true);

        $action = new AddLineItemAction([
            'quantity' => $quantity,
            'force' => $force === __('yes'),
            'ignore_cancelled' => $ignoreCancelled,
            'ignore_fulfilled' => $ignoreFulfilled
        ]);
        $action->product()->associate($product);

        return $action;
    }
}
