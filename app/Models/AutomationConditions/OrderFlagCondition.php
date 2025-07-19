<?php

namespace App\Models\AutomationConditions;

use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomationBaseObjectInterface;
use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationCondition;
use App\Models\Automations\AppliesToMany;
use App\Models\Automations\OrderAutomation;
use App\Models\Automations\OrderFlag;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderFlagCondition extends AutomationCondition
    implements AutomationConditionInterface, AutomationBaseObjectInterface
{
    use HasFactory, inheritanceHasParent, AppliesToMany;

    protected $fillable = [
        'field_name',
        'flag_value'
    ];

    protected $casts = [
        'field_name' => OrderFlag::class,
        'flag_value' => 'bool'
    ];

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    public function match(AutomatableEvent $event): bool
    {
        $order = $event->getOperation();
        $flagName = $this->field_name->value;

        return (bool) $order->$flagName === $this->flag_value; // Deliberately comparing same type.
    }

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::getTriggerPathByCondition(self::class),
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Order Flag';
    }

    public function getDescriptionAttribute(): String
    {
        return $this->getTitleAttribute();
    }
}
