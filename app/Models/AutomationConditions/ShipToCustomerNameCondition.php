<?php

namespace App\Models\AutomationConditions;

use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationCondition;
use App\Models\Automations\OrderTextField;
use App\Models\Automations\TextComparison;
use App\Traits\Automation\OrderTextFieldConditionTrait;

class ShipToCustomerNameCondition extends AutomationCondition implements AutomationConditionInterface
{
    use OrderTextFieldConditionTrait;

    protected $attributes = [
        'field_name' => OrderTextField::CUSTOMER_NAME,
    ];

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::getTriggerPathByCondition(OrderTextFieldCondition::class),
            'field_name' => OrderTextField::CUSTOMER_NAME->value
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Ship-to customer name';
    }
}
