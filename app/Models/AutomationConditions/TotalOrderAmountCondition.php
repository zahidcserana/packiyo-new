<?php

namespace App\Models\AutomationConditions;

use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationCondition;
use App\Models\Automations\NumberComparison;
use App\Models\Automations\OrderNumberField;
use App\Models\Automations\OrderTextField;
use App\Traits\Automation\OrderNumberFieldConditionTrait;

class TotalOrderAmountCondition extends AutomationCondition implements AutomationConditionInterface
{
    use OrderNumberFieldConditionTrait;

    protected $attributes = [
        'field_name' => OrderNumberField::TOTAL
    ];

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::getTriggerPathByCondition(OrderNumberFieldCondition::class),
            'field_name' => OrderNumberField::TOTAL->value
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Total order amount';
    }
}
