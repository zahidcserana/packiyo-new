<?php

namespace App\Models\AutomationConditions;

use App\Console\Commands\CreateAutomation\AddsAddPackingNoteAction;
use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomationBaseObjectInterface;
use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationCondition;
use App\Models\Automations\AppliesToMany;
use App\Models\Automations\ComparesNumbers;
use App\Models\Automations\NumberComparison;
use App\Models\Automations\OrderAutomation;
use App\Models\Automations\WeightUnit;
use App\Models\Customer;
use App\Models\CustomerSetting;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderWeightCondition extends AutomationCondition
    implements AutomationConditionInterface, AutomationBaseObjectInterface
{
    use HasFactory, inheritanceHasParent, AppliesToMany, ComparesNumbers;

    protected $fillable = [
        'comparison_operator',
        'number_field_value',
        'unit_of_measure'
    ];

    protected $casts = [
        'comparison_operator' => NumberComparison::class,
        'unit_of_measure' => WeightUnit::class
    ];

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    public function match(AutomatableEvent $event): bool
    {
        $order = $event->getOperation();
        $orderWeightUnit = customer_settings($order->customer->id, CustomerSetting::CUSTOMER_SETTING_WEIGHT_UNIT)
            ?? Customer::WEIGHT_UNIT_DEFAULT;
        $orderWeight = self::getWeightInOz($order->weight, $orderWeightUnit);
        $triggerWeight = self::getWeightInOz($this->number_field_value, $this->unit_of_measure->value);

        return $this->compare($orderWeight, $this->comparison_operator, $triggerWeight);
    }

    protected static function getWeightInOz(float $weight, string $weightUnit): float
    {
        $weightInOz = $weight;

        if ($weightUnit === 'lb') {
            $weightInOz = $weightInOz * 16;
        } elseif ($weightUnit === 'kg') {
            $weightInOz = $weightInOz * 35.274;
        } elseif ($weightUnit === 'g') {
            $weightInOz = $weightInOz * 0.035274;
        }

        return $weightInOz;
    }

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::getTriggerPathByCondition(self::class),
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Total order weight';
    }

    public function getDescriptionAttribute(): string
    {
        return sprintf(
            '%s %s %s %s',
            $this->getTitleAttribute(),
            NumberComparison::getReadableText($this->comparison_operator),
            $this->number_field_value,
            WeightUnit::getReadableText($this->unit_of_measure)
        );
    }
}
