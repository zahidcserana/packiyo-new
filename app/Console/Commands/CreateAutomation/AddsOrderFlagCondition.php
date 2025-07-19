<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\Automations\OrderFlag;
use App\Models\AutomationConditions\OrderFlagCondition;
use RuntimeException;

trait AddsOrderFlagCondition
{
    protected function addOrderFlagCondition(AutomationChoices $automationChoices): OrderFlagCondition|array
    {
        $flagName = $this->choice(
            __('Which flag should be evaluated?'),
            collect(OrderFlag::cases())->pluck('value')->toArray()
        );
        $flagValue = $this->choice(
            __('What should its value be?'),
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

        return new OrderFlagCondition([
            'field_name' => $flagName,
            'flag_value' => $flagValue
        ]);
    }
}
