<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\AutomationActions\MarkAsFulfilledAction;

trait AddsMarkAsFulfilledAction
{
    protected function addMarkAsFulfilledAction(AutomationChoices $automationChoices): MarkAsFulfilledAction
    {
        $ignoreCancelled = $this->confirm(__('Should cancelled orders be ignored?'), true);

        return new MarkAsFulfilledAction(['ignore_cancelled' => $ignoreCancelled]);
    }
}
