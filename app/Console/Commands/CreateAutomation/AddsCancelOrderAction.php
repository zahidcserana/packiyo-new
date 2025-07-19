<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\AutomationActions\CancelOrderAction;

trait AddsCancelOrderAction
{
    protected function addCancelOrderAction(AutomationChoices $automationChoices): CancelOrderAction
    {
        $ignoreFulfilled = $this->confirm(__('Should fulfilled orders be ignored?'), true);

        return new CancelOrderAction(['ignore_fulfilled' => $ignoreFulfilled]);
    }
}
