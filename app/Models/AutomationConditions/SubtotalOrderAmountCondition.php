<?php

namespace App\Models\AutomationConditions;

use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationCondition;
use App\Models\Automations\OrderNumberField;
use App\Traits\Automation\OrderNumberFieldConditionTrait;

class SubtotalOrderAmountCondition extends AutomationCondition implements AutomationConditionInterface
{
    use OrderNumberFieldConditionTrait;

    protected $attributes = [
        'field_name' => OrderNumberField::SUBTOTAL
    ];

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::getTriggerPathByCondition(OrderNumberFieldCondition::class),
            'field_name' => OrderNumberField::SUBTOTAL->value
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Sub total order amount';
    }
}
