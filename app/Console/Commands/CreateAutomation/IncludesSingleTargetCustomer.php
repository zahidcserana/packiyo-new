<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use Illuminate\Support\Collection;

trait IncludesSingleTargetCustomer
{
    protected static function getCustomersForFilter(AutomationChoices $automationChoices): Collection
    {
        $ownerCustomer = $automationChoices->getOwnerCustomer();
        $customers = [$ownerCustomer];
        $targetCustomers = $automationChoices->getTargetCustomers();

        if (!is_null($targetCustomers) && $targetCustomers->count() === 1) {
            $customers[] = $targetCustomers[0]; // Add single 3PL client.
        }

        return collect($customers);
    }
}
