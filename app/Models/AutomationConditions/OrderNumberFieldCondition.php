<?php

namespace App\Models\AutomationConditions;

use App\Interfaces\AutomationBaseObjectInterface;
use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationCondition;
use App\Traits\Automation\OrderNumberFieldConditionTrait;

class OrderNumberFieldCondition extends AutomationCondition
    implements AutomationConditionInterface, AutomationBaseObjectInterface
{
    use OrderNumberFieldConditionTrait;

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::getTriggerPathByCondition(self::class),
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Order number field';
    }
}
