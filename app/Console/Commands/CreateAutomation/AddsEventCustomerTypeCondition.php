<?php

namespace App\Console\Commands\CreateAutomation;

use App\Enums\EventUser;
use App\Enums\Source;
use App\Models\AutomationConditions\EventCustomerTypeCondition;

trait AddsEventCustomerTypeCondition
{
    protected function addEventCustomerTypeCondition(AutomationChoices $automationChoices): EventCustomerTypeCondition
    {
        $choices = [];
        $appliesTo = $this->choice(
            __('Which user type should be matched?'),
            collect(EventUser::cases())->pluck('value')->toArray()
        );
        $choices[] = $appliesTo;

        return new EventCustomerTypeCondition(
            ['text_field_values' => $choices]
        );
    }
}
