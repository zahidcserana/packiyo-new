<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\AutomationConditions\OrderIsManualCondition;

trait AddsOrderIsManualCondition
{
    protected function addOrderIsManualCondition(AutomationChoices $automationChoices): OrderIsManualCondition
    {
        $flagValue = $this->confirm(__('Should the order be manual?'), true);

        return new OrderIsManualCondition(['flag_value' => $flagValue]);
    }
}
