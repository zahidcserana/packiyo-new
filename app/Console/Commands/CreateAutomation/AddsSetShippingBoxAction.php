<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\AutomationActions\SetShippingBoxAction;
use App\Models\ShippingBox;

trait AddsSetShippingBoxAction
{
    use IncludesSingleTargetCustomer;

    protected function addSetShippingBoxAction(AutomationChoices $automationChoices): SetShippingBoxAction
    {
        $customers = $this->getCustomersForFilter($automationChoices);
        $customerIds = $customers->pluck('id')->toArray();

        $shippingBoxId = $this->choice(
            __('Which box should the orders be shipped with?'),
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

        $action = new SetShippingBoxAction();
        $action->shippingBox()->associate($shippingBox);

        return $action;
    }
}
