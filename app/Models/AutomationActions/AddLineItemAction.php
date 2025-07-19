<?php

namespace App\Models\AutomationActions;

use App\Exceptions\AutomationException;
use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomationBaseObjectInterface;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Interfaces\AutomationActionInterface;
use App\Models\AutomationAction;
use App\Models\Automations\AppliesToSingle;
use App\Models\Automations\OrderAutomation;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddLineItemAction extends AutomationAction implements AutomationActionInterface, AutomationBaseObjectInterface
{
    use HasFactory, inheritanceHasParent, AppliesToSingle;

    protected $fillable = [
        'quantity',
        'force',
        'ignore_cancelled',
        'ignore_fulfilled'
    ];

    protected $casts = [
        'force' => 'bool'
    ];

    protected $attributes = [
        'force' => false
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id'); // Although it doesn't belong to it.
    }

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    public static function loadForCommand(): array
    {
        return ['product'];
    }

    public function run(AutomatableEvent $event): void
    {
        $order = $event->getOperation();
        $orderItem = $order->orderItems->filter(fn (OrderItem $item) => $item->product_id == $this->product_id)->first();

        // TODO: Define whether a condition/action can apply to many customers.
        if ($order->customer_id != $this->product->customer_id) {
            throw new AutomationException(
                'The order ' . $order->number . ' does not belong to customer '
                . $this->product->customer->contactInformation->name
                . ' so the product ' . $this->product->sku . ' cannot be added.'
            );
        }

        if ($this->ignore_fulfilled && $order->fulfilled_at) {
            return;
        }

        if ($this->ignore_cancelled && $order->cancelled_at) {
            return;
        }

        if ($orderItem) {
            $quantity = $this->getQuantityToAdd($orderItem);

            if ($quantity) {
                $this->addQuantityToOrderItem($orderItem, $quantity);
            }
        } else {
            app('order')->updateOrderItems($order, [[
                'product_id' => $this->product_id,
                'quantity' => $this->quantity
            ]]);
        }
    }

    private function getQuantityToAdd(OrderItem $orderItem): int
    {
        $quantity = 0;

        if ($this->force) {
            $quantity = $this->quantity;
        } elseif ($orderItem->quantity < $this->quantity) {
            $quantity = $this->quantity - $orderItem->quantity;
        }

        return $quantity;
    }

    private function addQuantityToOrderItem(OrderItem $orderItem, float $quantity): void
    {
        $orderItem->quantity += $quantity;
        $orderItem->save();
    }

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::class,
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Add line item';
    }

    public function getDescriptionAttribute(): string
    {
        return sprintf('%s: %d units of "%s"', $this->getTitleAttribute(),
            $this->quantity, $this->product?->name);
    }
}
