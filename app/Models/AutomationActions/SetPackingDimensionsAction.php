<?php

namespace App\Models\AutomationActions;

use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomationBaseObjectInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Interfaces\AutomationActionInterface;
use App\Models\AutomationAction;
use App\Models\Automations\AppliesToMany;
use App\Models\Automations\OrderAutomation;
use App\Models\OrderItem;
use App\Models\OrderItemIterator;
use App\Models\ShippingBox;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasParent;
use stdClass;

class SetPackingDimensionsAction extends AutomationAction
    implements AutomationActionInterface, AutomationBaseObjectInterface
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
        [$length, $width, $height] = static::calculateDimensions(new OrderItemIterator($order));

        foreach (['length', 'width', 'height'] as $name) {
            $order->{'packing_' . $name} = $$name;
        }

        $order->shippingBox()->associate($this->shippingBox);
        $order->save();
    }

    protected static function calculateDimensions(OrderItemIterator $orderItemIterator): array
    {
        $length = 0; // Adjusting to the greater dimension.
        $width = 0; // Adjusting to the middle dimension.
        $height = 0; // Adding the lesser dimension here, as though stacking the products.

        foreach ($orderItemIterator as $orderItem) {
            $orderItemDimensions = static::createDimensions($orderItem);
            $orderItemDimensions = collect($orderItemDimensions)->sortByDesc('value')->values();

            if ($length < $orderItemDimensions[0]->value) {
                $length = $orderItemDimensions[0]->value;
            }

            if ($width < $orderItemDimensions[1]->value) {
                $width = $orderItemDimensions[1]->value;
            }

            $height += $orderItemDimensions[2]->value * $orderItem->quantity;
        }

        return [$length, $width, $height];
    }

    protected static function createDimensions(OrderItem $orderItem): array
    {
        $dimensions = [];

        foreach (['length', 'width', 'height'] as $name) {
            $dimension = new stdClass();
            $dimension->name = $name;
            $dimension->value = $orderItem->product->$name;
            $dimensions[] = $dimension;
        }

        return $dimensions;
    }

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::class,
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Set Packing Dimensions';
    }

    public function getDescriptionAttribute(): String
    {
        return $this->getTitleAttribute();
    }
}
