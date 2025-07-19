<?php

namespace App\Models\AutomationActions;

use App\Exceptions\AutomationException;
use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomationBaseObjectInterface;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Interfaces\AutomationActionInterface;
use App\Models\AutomationAction;
use App\Models\Automations\AppliesToMany;
use App\Models\Automations\OrderAutomation;
use App\Models\Order;

class SetDeliveryConfirmationAction extends AutomationAction
    implements AutomationActionInterface, AutomationBaseObjectInterface
{
    use HasFactory, inheritanceHasParent, AppliesToMany;

    protected $fillable = [
        'text_field_value'
    ];

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    public function run(AutomatableEvent $event): void
    {
        $order = $event->getOperation();

        if (!in_array($this->text_field_value, [
            Order::DELIVERY_CONFIRMATION_SIGNATURE,
            Order::DELIVERY_CONFIRMATION_NO_SIGNATURE,
            Order::DELIVERY_CONFIRMATION_ADULT_SIGNATURE
        ])) {
            throw new AutomationException('Invalid delivery confirmation value.');
        }

        $order->delivery_confirmation = $this->text_field_value;
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
        return 'Set Delivery Confirmation';
    }

    public function getDescriptionAttribute(): String
    {
        return $this->getTitleAttribute();
    }
}
