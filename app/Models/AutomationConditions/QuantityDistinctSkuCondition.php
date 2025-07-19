<?php

namespace App\Models\AutomationConditions;

use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationCondition;
use App\Models\Automations\AppliesToLineItems;
use App\Traits\Automation\OrderLineItemConditionTrait;

class QuantityDistinctSkuCondition extends AutomationCondition implements AutomationConditionInterface
{
    use OrderLineItemConditionTrait;

    protected $attributes = [
        'applies_to' => AppliesToLineItems::ALL,
    ];

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::getTriggerPathByCondition(OrderLineItemCondition::class),
            'applies_to' => AppliesToLineItems::ALL->value,
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Quantity of distinct SKUs';
    }
}
