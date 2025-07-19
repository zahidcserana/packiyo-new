<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\AutomationActions\SetDeliveryConfirmationAction;
use App\Models\Order;

trait AddsSetDeliveryConfirmationAction
{
    protected function addSetDeliveryConfirmationAction(AutomationChoices $automationChoices): SetDeliveryConfirmationAction
    {
        $fieldValue = $this->choice(__('Should a signature be required?'), [
            Order::DELIVERY_CONFIRMATION_SIGNATURE,
            Order::DELIVERY_CONFIRMATION_NO_SIGNATURE,
            Order::DELIVERY_CONFIRMATION_ADULT_SIGNATURE
        ]);

        return new SetDeliveryConfirmationAction([
            'text_field_value' => $fieldValue
        ]);
    }
}
