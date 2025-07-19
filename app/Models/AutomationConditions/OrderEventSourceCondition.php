<?php

namespace App\Models\AutomationConditions;

use App\Events\OrderCreatedEvent;
use App\Events\OrderUpdatedEvent;
use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomationBaseObjectInterface;
use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationCondition;
use App\Models\Automations\AppliesToMany;
use App\Models\Automations\ConstComparison;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderEventSourceCondition extends AutomationCondition
    implements AutomationConditionInterface, AutomationBaseObjectInterface
{
    use HasFactory, inheritanceHasParent, AppliesToMany;

    protected $fillable = [
        'text_field_values',
        'comparison_operator'
    ];

    protected $casts = [
        'text_field_values' => 'array',
        'comparison_operator' => ConstComparison::class,
    ];

    public static function getSupportedEvents(): array
    {
        return [
            OrderCreatedEvent::class,
            OrderUpdatedEvent::class
        ];
    }

    public function match(OrderCreatedEvent|OrderUpdatedEvent|AutomatableEvent $event): bool
    {
        $match = false;
        $source = $event->getEventSource();

        if (!empty($source)) {
            $triggerValues = collect($this->text_field_values);

            if ($this->comparison_operator == ConstComparison::IS_ONE_OF) {
                $match = $triggerValues->contains($source->value);
            } elseif ($this->comparison_operator == ConstComparison::IS_NONE_OF) {
                $match = !$triggerValues->contains($source->value);
            }
        }

        return $match;
    }

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::getTriggerPathByCondition(self::class),
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Order Event Source';
    }

    public function getDescriptionAttribute(): String
    {
        return $this->getTitleAttribute();
    }
}
