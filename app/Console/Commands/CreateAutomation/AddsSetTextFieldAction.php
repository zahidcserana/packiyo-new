<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\AutomationActions\SetTextFieldAction;
use App\Models\Automations\Incoterms;
use App\Models\Automations\OrderTextField;
use App\Models\Automations\RegionCode;

trait AddsSetTextFieldAction
{
    protected function addSetTextFieldAction(AutomationChoices $automationChoices): SetTextFieldAction
    {
        $fieldName = $this->choice(
            __('Which field do you want to set?'),
            collect(OrderTextField::writable())->pluck('value')->toArray()
        );
        $choices = [];

        if ($fieldName == OrderTextField::INCOTERMS->value) {
            $choices = collect(Incoterms::cases())->pluck('value')->toArray();
        }

        $fieldValue = $this->anticipate(__('What should the field be set to?'), $choices);

        return new SetTextFieldAction([
            'field_name' => $fieldName,
            'text_field_value' => $fieldValue
        ]);
    }
}
