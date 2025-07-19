<?php

namespace App\Models\AutomationConditions;

use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationCondition;
use App\Models\Automations\OrderTextField;
use App\Traits\Automation\OrderTextFieldConditionTrait;

class ShipToStateCondition extends AutomationCondition implements AutomationConditionInterface
{
    use OrderTextFieldConditionTrait;

    protected $attributes = [
        'field_name' => OrderTextField::SHIPPING_STATE,
    ];

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::getTriggerPathByCondition(OrderTextFieldCondition::class),
            'field_name' => OrderTextField::SHIPPING_STATE->value
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Ship-to state';
    }
}
