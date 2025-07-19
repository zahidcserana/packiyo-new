<?php

namespace App\Models\AutomationConditions;

use App\Interfaces\AutomationBaseObjectInterface;
use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationCondition;
use App\Traits\Automation\OrderLineItemConditionTrait;

class OrderLineItemCondition extends AutomationCondition
    implements AutomationConditionInterface, AutomationBaseObjectInterface
{
    use OrderLineItemConditionTrait;

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::getTriggerPathByCondition(self::class),
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Order line item(s) (SKUs)';
    }
}
