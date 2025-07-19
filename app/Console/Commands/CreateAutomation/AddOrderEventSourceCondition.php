<?php

namespace App\Console\Commands\CreateAutomation;

use App\Enums\Source;
use App\Models\AutomationConditions\OrderEventSourceCondition;
use App\Models\Automations\ConstComparison;

trait AddOrderEventSourceCondition
{
    protected function addOrderEventSourceCondition(AutomationChoices $automationChoices): OrderEventSourceCondition
    {
        $choices = [];

        do {
            $appliesTo = $this->choice(
                __('Which source type should be matched?'),
                collect(Source::cases())->pluck('value')->toArray()
            );
            $choices[] = $appliesTo;
        } while ($this->confirm(__('Do you want to add another source type?'), false));

        $operator = $this->choice(
            __('How should the field be compared?'),
            collect(ConstComparison::cases())->pluck('value')->toArray()
        );

        return new OrderEventSourceCondition(
            [
                'text_field_values' => $choices,
                'comparison_operator' => $operator,
            ]
        );
    }
}
