<?php

namespace App\Models\AutomationConditions;

use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationCondition;
use App\Models\Automations\OrderTextField;
use App\Models\Automations\TextComparison;
use App\Traits\Automation\OrderTextFieldConditionTrait;

class SalesChannelCondition extends AutomationCondition implements AutomationConditionInterface
{
    use OrderTextFieldConditionTrait;

    protected $attributes = [
        'field_name' => OrderTextField::CHANNEL_NAME,
    ];

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::getTriggerPathByCondition(OrderTextFieldCondition::class),
            'field_name' => OrderTextField::CHANNEL_NAME->value
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Sales channel';
    }
}
