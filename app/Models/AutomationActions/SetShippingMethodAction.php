<?php

namespace App\Models\AutomationActions;

use App\Events\OrderUpdatedEvent;
use App\Events\OrderUpdateField;
use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomatableOperation;
use App\Interfaces\AutomationBaseObjectInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Interfaces\AutomationActionInterface;
use App\Models\AutomationAction;
use App\Models\Automations\AppliesToMany;
use App\Models\Automations\OrderAutomation;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasParent;

class SetShippingMethodAction extends AutomationAction
    implements AutomationActionInterface, AutomationBaseObjectInterface
{
    use HasFactory, HasParent, AppliesToMany;

    protected $fillable = [
        'force'
    ];

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id'); // Although it doesn't belong to it.
    }

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    public static function loadForCommand(): array
    {
        return ['shippingMethod.shippingCarrier'];
    }

    public function run(AutomatableEvent $event): void
    {
        $order = $event->getOperation();

        if ($this->shouldNotRun($event)) {
            return;
        }

        $this->setShippingMethod($order);
    }

    /**
     * Should not run if the shipping method has been manually changed, and the action is not configured to force.
     */
    public function shouldNotRun(AutomatableEvent $event): bool
    {
        return $event instanceof OrderUpdatedEvent &&
            !$this->force &&
            $event->hasChanged(OrderUpdateField::ShippingMethod);
    }

    public function setShippingMethod(AutomatableOperation $order): void
    {
        $order->shippingMethod()->associate($this->shippingMethod);
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
        return 'Set shipping method';
    }

    public function getDescriptionAttribute(): string
    {
        return sprintf('%s as %s', $this->getTitleAttribute(), $this->shippingMethod?->name);
    }
}
