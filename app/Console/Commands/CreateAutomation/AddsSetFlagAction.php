<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\AutomationActions\SetFlagAction;
use App\Models\Automations\OrderFlag;
use RuntimeException;

trait AddsSetFlagAction
{
    protected function addSetFlagAction(AutomationChoices $automationChoices): SetFlagAction
    {
        $flagName = $this->choice(
            __('Which flag do you want to set?'),
            collect(OrderFlag::cases())->pluck('value')->toArray()
        );
        $flagValue = $this->choice(
            __('What should the flag be set to?'),
            [__('on'), __('off')], // Choices.
            __('on') // Default.
        );

        if ($flagValue == __('on')) {
            $flagValue = true;
        } elseif ($flagValue == __('off')) {
            $flagValue = false;
        } else {
            throw new RuntimeException('Invalid flag value given.');
        }

        return new SetFlagAction([
            'field_name' => $flagName,
            'flag_value' => $flagValue
        ]);
    }
}
