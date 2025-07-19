<?php

namespace App\Observers;

use App\Features\MultiWarehouse;
use App\Jobs\AllocateInventoryJob;
use App\Models\Order;
use App\Models\OrderItem;
use Laravel\Pennant\Feature;
use App\Models\Product;

class OrderItemObserver
{
    /**
     * Handle the OrderItem "created" event.
     *
     * @param  OrderItem $orderItem
     * @return void
     */
    public function created(OrderItem $orderItem) : void
    {
        Order::disableAuditing();

        $orderItem->order->save();

        if ($orderItem->product) {
            if (Feature::for('instance')->active(MultiWarehouse::class)) {
                AllocateInventoryJob::dispatch($orderItem->product, $orderItem->order->warehouse);
            } else {
                AllocateInventoryJob::dispatch($orderItem->product);
            }
        }

        if ($orderItem->isComponent() && $orderItem->wasRecentlyCreated) {
            app('order')->auditChangesInParentKit($orderItem, __('created'));
        }
    }

    /**
     * Handle the OrderItem "creating" event.
     *
     * @param  OrderItem $orderItem
     * @return void
     */
    public function creating(OrderItem $orderItem) : void
    {
        app('order')->updateOrderItemDetails($orderItem);

        if (empty($orderItem->ordered_at)) {
            $orderItem->ordered_at = $orderItem->order->ordered_at;
        }

        if ($orderItem->product && $orderItem->product->type === Product::PRODUCT_TYPE_VIRTUAL) {
            $orderItem->quantity_shipped = $orderItem->quantity;
            $orderItem->quantity_pending = 0;
        } else {
            $orderItem->quantity_pending = $orderItem->quantity;
        }

    }

    /**
     * Handle the OrderItem "updated" event.
     *
     * @param  OrderItem $orderItem
     * @return void
     */
    public function updated(OrderItem $orderItem): void
    {
        Order::disableAuditing();

        if ($orderItem->wasChanged('quantity_pending')) {
            $orderItem->order->update(['batch_key' => null]);

            if ($orderItem->product) {
                if (Feature::for('instance')->active(MultiWarehouse::class)) {
                    AllocateInventoryJob::dispatch($orderItem->product, $orderItem->order->warehouse);
                } else {
                    AllocateInventoryJob::dispatch($orderItem->product);
                }
            }
        }

        if ($orderItem->wasChanged('product_id') && $orderItem->product_id) {
            app('order')->updateOrderItemDetails($orderItem);
            if (Feature::for('instance')->active(MultiWarehouse::class)) {
                AllocateInventoryJob::dispatch($orderItem->product, $orderItem->order->warehouse);
            } else {
                AllocateInventoryJob::dispatch($orderItem->product);
            }
        }

        if ($orderItem->isComponent() && $orderItem->wasChanged('quantity')) {
            app('order')->auditChangesInParentKit($orderItem, __('updated'));
        }

        $orderItem->order->save();
    }

    /**
     * Handle the OrderItem "saving" event.
     *
     * @param  OrderItem $orderItem
     * @return void
     */
    public function saving(OrderItem $orderItem): void
    {
        if (!$orderItem->exists && !isset($orderItem->quantity_pending)) {
            $orderItem->quantity_pending = $orderItem->quantity;
        }

        $orderItem->quantity_shipped = max($orderItem->quantity_shipped, $orderItem->shipmentItems()->sum('quantity'));

        if (!$orderItem->order->cancelled_at && !$orderItem->order->fulfilled_at && !$orderItem->order->archived_at && !$orderItem->cancelled_at) {
            $orderItem->quantity_pending = $orderItem->quantity - $orderItem->quantity_shipped + $orderItem->quantity_reshipped;
        }

        $orderItem->quantity_pending = max(0, $orderItem->quantity_pending);

        if ($orderItem->cancelled_at || $orderItem->archived_at) {
            $orderItem->quantity_pending = 0;
        }

        $parentOrderItem = $orderItem->parentOrderItem;

        if ($parentOrderItem && $parentOrderItem->quantity_pending < 1) {
            $orderItem->quantity_pending = 0;
        }

        if ($orderItem->product && $orderItem->product->type === Product::PRODUCT_TYPE_VIRTUAL) {
            $orderItem->quantity_shipped = $orderItem->quantity;
            $orderItem->quantity_pending = 0;
        }

        // De-allocate the item so that there's no window where allocate inventory job
        // hasn't finished and new picking batch is being created
        if ($orderItem->isDirty('quantity_shipped')) {
            $quantityShipped = $orderItem->quantity_shipped - $orderItem->getOriginal('quantity_shipped');

            $orderItem->quantity_allocated = max(0, $orderItem->quantity_allocated - $quantityShipped);
            $orderItem->quantity_allocated_pickable = max(0, $orderItem->quantity_allocated_pickable - $quantityShipped);
        }

        if ($orderItem->isComponent() && !$orderItem->component_quantity) {
            $orderItem->component_quantity = $orderItem->componentQuantityForKit();
        }
    }

    /**
     * Handle the order item "deleted" event.
     *
     * @param  OrderItem  $orderItem
     * @return void
     */
    public function deleted(OrderItem $orderItem): void
    {
        $orderItem->order->save();

        if ($orderItem->product) {
            if (Feature::for('instance')->active(MultiWarehouse::class)) {
                AllocateInventoryJob::dispatch($orderItem->product, $orderItem->order->warehouse);
            } else {
                AllocateInventoryJob::dispatch($orderItem->product);
            }
        }
    }
}
