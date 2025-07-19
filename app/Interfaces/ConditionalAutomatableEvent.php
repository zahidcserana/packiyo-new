<?php

namespace App\Interfaces;

use App\Models\AutomationEventCondition;

interface ConditionalAutomatableEvent extends AutomatableEvent
{
    public function getCondition(): AutomationEventCondition;

    public function runAutomationOnSelf(): void;
}
