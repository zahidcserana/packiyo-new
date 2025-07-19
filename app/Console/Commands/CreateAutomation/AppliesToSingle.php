<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Exceptions\AutomationException;
use App\Models\Customer;
use LogicException;

trait AppliesToSingle
{
    protected static function getCustomerFromChoices(AutomationChoices $automationChoices): Customer
    {
        $ownerCustomer = $automationChoices->getOwnerCustomer();
        $targetCustomers = $automationChoices->getTargetCustomers();

        if (!is_null($targetCustomers)) {
            if ($targetCustomers->count() === 1) {
                return $targetCustomers[0];
            } else {
                throw new AutomationException(
                    'Cannot add ' . self::class . ' to an automation targetting more than one customer.'
                );
            }
        } elseif ($ownerCustomer->is3pl()) { // Applies to all clients.
            throw new AutomationException(
                'Cannot add ' . self::class . ' to an automation targetting more than one customer.'
            );
        } elseif ($ownerCustomer->isStandalone()) {
            return $ownerCustomer;
        } else {
            throw new LogicException('This should be unreachable.');
        }
    }
}
