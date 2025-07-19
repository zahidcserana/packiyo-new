<?php

namespace App\Models\AutomationActions;

use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomationBaseObjectInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Interfaces\AutomationActionInterface;
use App\Models\AutomationAction;
use App\Models\Automations\AppliesToMany;
use App\Models\Automations\OrderAutomation;
use App\Models\ShippingBox;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasParent;

class SetShippingBoxAction extends AutomationAction implements AutomationActionInterface, AutomationBaseObjectInterface
{
    use HasFactory, HasParent, AppliesToMany;

    public function shippingBox(): BelongsTo
    {
        return $this->belongsTo(ShippingBox::class, 'shipping_box_id'); // Although it doesn't belong to it.
    }

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    public static function loadForCommand(): array
    {
        return ['shippingBox'];
    }

    public function run(AutomatableEvent $event): void
    {
        $order = $event->getOperation();
        $order->shippingBox()->associate($this->shippingBox);
        $order->save();
    }

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::class,
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Set Shipping Box';
    }

    public function getDescriptionAttribute(): String
    {
        return $this->getTitleAttribute();
    }
}
