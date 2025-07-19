<?php

namespace App\Models\AutomationConditions;

use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomationBaseObjectInterface;
use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationCondition;
use App\Models\Automations\AppliesToSingle;
use App\Models\Automations\OrderAutomation;
use App\Models\OrderChannel;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderChannelCondition extends AutomationCondition
    implements AutomationConditionInterface, AutomationBaseObjectInterface
{
    use HasFactory, inheritanceHasParent, AppliesToSingle;

    public function orderChannel(): BelongsTo
    {
        return $this->belongsTo(OrderChannel::class, 'order_channel_id'); // Although it doesn't belong to it.
    }

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    public static function loadForCommand(): array
    {
        return ['orderChannel'];
    }

    public function match(AutomatableEvent $event): bool
    {
        $order = $event->getOperation();
        return $order->orderChannel && $this->orderChannel && $order->orderChannel->id == $this->orderChannel->id;
    }

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::getTriggerPathByCondition(self::class),
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Order Channel';
    }

    public function getDescriptionAttribute(): String
    {
        return $this->getTitleAttribute();
    }
}
