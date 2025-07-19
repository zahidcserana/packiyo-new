<?php

namespace App\Console\Commands;

use App\Models\Automation;
use App\Models\Customer;

trait NamesAutomations
{
    protected function getGivenName(Customer $ownerCustomer, bool $renaming = false): string
    {
        do {
            if (isset($name)) {
                $this->line(__('It seems this customer already has an automation with that name.'));
            }

            $name = trim($this->ask($renaming
                ? __('What should the automation be renamed to?')
                : __('What should the automation be named?')
            ));
        } while (Automation::where(['customer_id' => $ownerCustomer->id, 'name' => $name])->exists());

        return $name;
    }
}
