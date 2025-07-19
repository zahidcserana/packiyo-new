<?php

namespace App\Models\AutomationConditions;

use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationCondition;
use App\Models\Automations\AppliesToItemsQuantity;
use App\Models\Automations\NumberComparison;
use App\Traits\Automation\OrderLineItemsConditionTrait;

class QuantityOrderItemsCondition extends AutomationCondition implements AutomationConditionInterface
{
    use OrderLineItemsConditionTrait;

    protected $attributes = [
        'applies_to' =>  AppliesToItemsQuantity::TOTAL,
    ];

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::getTriggerPathByCondition(OrderLineItemsCondition::class),
            'applies_to' => AppliesToItemsQuantity::TOTAL->value
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Quantity of items in the order';
    }

    public function getDescriptionAttribute(): string
    {
        return sprintf(
            '%s %s %s',
            $this->getTitleAttribute(),
            NumberComparison::getReadableText($this->comparison_operator),
            $this->number_field_value
        );
    }
}
