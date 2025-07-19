<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\AutomationActions\ChargeShippingBoxRateAction;
use App\Models\Automations\AppliesToLineItems;
use App\Models\ShippingBox;

trait AddsChargeShippingBoxRateAction
{
    use AppliesToSingle;

    protected function addChargeShippingBoxRateAction(AutomationChoices $automationChoices): ChargeShippingBoxRateAction
    {
        $appliesTo = $this->choice(
            __('Which shipping boxes should this apply to?'),
            collect(AppliesToLineItems::cases())->pluck('value')->toArray()
        );
        $shippingBox = null;

        if ($appliesTo == AppliesToLineItems::SOME->value) {
            $customers = $this->getCustomersForFilter($automationChoices);
            $customerIds = $customers->pluck('id')->toArray();
            $shippingBoxId = $this->choice(
                __('Which box should be charged for?'),
                ShippingBox::whereIn('customer_id', $customerIds)
                    ->get()
                    ->mapWithKeys(fn (ShippingBox $box) => [__('ID :id', ['id' => $box->id])
                        => __(':name (:length x :width x :height)', [
                            'name' => $box->name,
                            'length' => $box->length,
                            'width' => $box->width,
                            'height' => $box->height
                        ])
                    ])
                    ->toArray()
            );
            $shippingBox = ShippingBox::findOrFail((int) substr($shippingBoxId, 3));
        }

        $amount = (float) $this->ask(__('How much should be charged per shipped box?'));
        $action = new ChargeShippingBoxRateAction([
            'applies_to',
            'amount' => $amount
        ]);

        if (!is_null($shippingBox)) {
            $action->shippingBox()->associate($shippingBox);
        }

        return $action;
    }
}
