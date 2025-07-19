<?php

namespace App\Console\Commands\CreateAutomation;

use App\Models\AutomationEventConditions\OrderAgedEventCondition;
use App\Models\Automations\TimeUnit;

trait AddsOrderAgedEventCondition
{
    protected function addOrderAgedEventCondition(): OrderAgedEventCondition
    {
        $unit = $this->choice(
            __('What is the unit of measure for the age?'),
            collect(TimeUnit::cases())->pluck('value')->toArray()
        );
        $value = (float) $this->ask(__('What is the age threshold for the event?'));
        $pendingOnly = $this->confirm(__('Should this only apply to pending orders?'), true);
        $ignoreHolds = $this->confirm(__('Should order holds be ignored?'), true);

        return new OrderAgedEventCondition([
            'number_field_value' => $value,
            'unit_of_measure' => $unit,
            'pending_only' => $pendingOnly,
            'ignore_holds' => $ignoreHolds
        ]);
    }
}
