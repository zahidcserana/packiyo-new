<?php

namespace App\Components\Allocation;

use App\Features\AllowNonSellableAllocation;
use App\Features\RequiredReadyToPickForPacking;
use Illuminate\Support\Facades\Log;
use Laravel\Pennant\Feature;
use App\Models\{LocationProduct, OrderItem, Product};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AllocationComponent
{
    /**
     * @param Product $product
     * @return void
     */
    public function allocateInventory(Product $product): void
    {
        if ($product->isKit()) {
            $this->calculateKitQuantities($product);

            $quantityOnHand = $product->quantity_on_hand;
            $quantityPickable = $product->quantity_pickable;
        } else {
            $quantityOnHand = $this->recalculateQuantityOnHand($product);
            $quantityPickable = $this->recalculateQuantityPickable($product);
            $quantityNonSellable = $this->recalculateNonSellableQuantity($product);

            if (Feature::for('instance')->inactive(AllowNonSellableAllocation::class)) {
                $quantityOnHand -= $quantityNonSellable;
            }
        }

        $quantityOnHand = max(0, $quantityOnHand);
        $quantityOnHandRemaining = $quantityOnHand;

        $quantityPickableRemaining = $quantityPickable;

        $quantityBackordered = 0;

        $orderItems = $product->orderItem()
            ->select('order_items.*', 'orders.allocation_hold')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where(function(Builder $query) {
                $query->where('quantity_pending', '!=', 0)
                    ->orWhere('quantity_allocated', '!=', 0)
                    ->orWhere('quantity_allocated_pickable', '!=', 0)
                    ->orWhere('quantity_backordered', '!=', 0);
            })
            ->orderByDesc('order_items.quantity_in_tote')
            ->orderByDesc('orders.priority_score')
            ->orderBy('orders.ordered_at')
            ->get();

        $orderIdsToReprocess = [];

        foreach ($orderItems as $orderItem) {
            $orderItemQuantityPending = max($orderItem->quantity_pending, 0);

            if ($orderItem->allocation_hold) {
                $orderItemQuantityAllocated = 0;
                $orderItemQuantityAllocatedPickable = 0;
                $orderItemQuantityBackordered = 0;
            } else {
                $quantityToAllocate = min($orderItemQuantityPending, $quantityOnHandRemaining);
                $quantityToAllocatePickable = min($orderItemQuantityPending, $quantityPickableRemaining);

                $orderItemQuantityAllocated = $quantityToAllocate;
                $orderItemQuantityAllocatedPickable = $quantityToAllocatePickable;
                $orderItemQuantityBackordered = $orderItemQuantityPending - $orderItemQuantityAllocated;

                $quantityOnHandRemaining -= $quantityToAllocate;
                $quantityPickableRemaining -= $quantityToAllocatePickable;
            }

            $orderItem->updateQuietly([
                'quantity_allocated' => $orderItemQuantityAllocated,
                'quantity_allocated_pickable' => $orderItemQuantityAllocatedPickable,
                'quantity_backordered' => $orderItemQuantityBackordered
            ]);

            $quantityBackordered += $orderItemQuantityBackordered;

            $orderIdsToReprocess[$orderItem->order_id] = $orderItem->order_id;
        }

        if (!empty($orderIdsToReprocess)) {
            app('order')->updateSummedQuantitiesV2($orderIdsToReprocess);
        }

        $product->updateQuietly([
            'quantity_allocated' => $quantityOnHand - $quantityOnHandRemaining,
            'quantity_allocated_pickable' => $quantityPickable - $quantityPickableRemaining,
            'quantity_backordered' => $quantityBackordered,
            'quantity_to_replenish' => ($quantityOnHand - $quantityOnHandRemaining) - ($quantityPickable - $quantityPickableRemaining)
        ]);

        $this->recalculateQuantityAvailable($product);

        foreach ($product->kitParents as $kitParent) {
            if ($product->id == $kitParent->id) {
                Log::warning("Refusing to allocate a recursive kit, SKU {$product->sku}.");
                break;
            }

            $this->allocateInventory($kitParent);
        }
    }

    public function recalculateQuantityOnHand(Product $product)
    {
        $quantityOnHand = LocationProduct::join('locations', 'location_product.location_id', '=', 'locations.id')
            ->where('product_id', $product->id)
            ->sum('quantity_on_hand');

        $product->update([
            'quantity_on_hand' => $quantityOnHand
        ]);

        return $quantityOnHand;
    }

    public function recalculateQuantityAvailable(Product $product): int
    {
        $product = $product->refresh();

        $quantityNonSellable = LocationProduct::join('locations', 'location_product.location_id', '=', 'locations.id')
            ->where('product_id', $product->id)
            ->where('locations.sellable_effective', 0)
            ->sum('quantity_on_hand');

        $quantityAvailable = $product->quantity_on_hand - $quantityNonSellable - $product->quantity_allocated + $this->getSellAheadQuantities($product) - $product->quantity_backordered - $product->quantity_reserved;

        $product->update([
            'quantity_available' => max(0, $quantityAvailable)
        ]);

        return $quantityAvailable;
    }

    public function recalculateQuantityPickable(Product $product)
    {
        $quantityPickable = Product::query()
            ->where('products.id', $product->id)
            ->join('location_product', 'location_product.product_id','=', 'products.id')
            ->join('locations', 'location_product.location_id','=', 'locations.id')
            ->where('locations.sellable_effective', 1)
            ->where('locations.pickable_effective', 1)
            ->when(
                Feature::for('instance')->inactive(RequiredReadyToPickForPacking::class),
                fn (Builder $builder) => $builder->where('locations.disabled_on_picking_app_effective', 0)
            )
            ->sum('location_product.quantity_on_hand');

        $product->update([
            'quantity_pickable' => (int) $quantityPickable
        ]);

        return $quantityPickable;
    }

    public function recalculateNonSellableQuantity(Product $product): int
    {
        $quantityNonSellable = LocationProduct::join('locations', 'location_product.location_id', '=', 'locations.id')
            ->where('product_id', $product->id)
            ->where('locations.sellable_effective', 0)
            ->sum('quantity_on_hand');

        $product->update([
            'quantity_non_sellable' => (int) $quantityNonSellable
        ]);

        return $quantityNonSellable;
    }

    /**
     * @param Product $product
     * @return int
     */
    public function getSellAheadQuantities(Product $product): int
    {
        $quantitySellAhead = 0;

        foreach ($product->purchaseOrderLine as $purchaseOrderItem) {
            $quantitySellAhead += $purchaseOrderItem->quantity_sell_ahead;
        }

        return $quantitySellAhead;
    }

    /**
     * Kit parent on hand is calculated based on available on hand of the components. Additionally, we increase kit
     * parent on hand by the calculated allocated quantities of the components so the available is calculated correctly.
     *
     * @param Product $product
     * @return void
     */
    private function calculateKitQuantities(Product $product): void
    {
        $quantitiesAvailable = [];
        $quantitiesAllocated = [];

        foreach ($product->kitItems as $component) {
            $componentQuantity = $component->pivot->quantity ?: 0;

            if (!$componentQuantity) {
                continue;
            }

            $componentQuantityAllocated = OrderItem::where('product_id', $component->id)
                ->whereHas('parentOrderItem', function($query) use ($product) {
                    $query->where('product_id', $product->id);
                })
                ->sum('quantity_allocated');

            $quantitiesAvailable[] = intdiv($component->quantity_available, $componentQuantity);
            $quantitiesAllocated[] = intdiv($componentQuantityAllocated, $componentQuantity);
        }

        $quantityAvailable = empty($quantitiesAvailable) ? 0 : min($quantitiesAvailable);
        $quantityAllocated = empty($quantitiesAllocated) ? 0 : min($quantitiesAllocated);

        $product->quantity_on_hand = $quantityAvailable + $quantityAllocated;
        $product->quantity_pickable = $product->quantity_on_hand;

        $product->saveQuietly();

        if ($product->wasChanged('quantity_on_hand')) {
            app('inventoryLog')->triggerAdjustInventoryWebhook($product);
        }
    }

    /**
     * @param Product $product
     * @return void
     */
    private function updateSummedQuantities(Product $product): void
    {
        DB::transaction(static function () use ($product) {
            DB::update(
                'UPDATE `products` LEFT JOIN
                        (SELECT
                            `product_id`,
                            SUM(`quantity_allocated`) AS `order_items_quantity_allocated_sum`,
                            SUM(`quantity_allocated_pickable`) AS `order_items_quantity_allocated_pickable_sum`,
                            SUM(`quantity_backordered`) AS `order_items_quantity_backordered_sum`
                        FROM `order_items`
                        WHERE
                            `product_id` = :product_id1
                            AND (`quantity_allocated` != 0 OR `quantity_allocated` != 0 OR `quantity_allocated_pickable` != 0 OR `quantity_backordered` != 0)
                        GROUP BY `product_id`) `summed_order_items`
                ON `products`.`id` = `summed_order_items`.`product_id`
                SET
                    `quantity_allocated` = IFNULL(`order_items_quantity_allocated_sum`, 0),
                    `quantity_allocated_pickable` = IFNULL(`order_items_quantity_allocated_pickable_sum`, 0),
                    `quantity_backordered` = IFNULL(`order_items_quantity_backordered_sum`, 0),
                    `quantity_to_replenish` = GREATEST(IFNULL(`order_items_quantity_allocated_sum`, 0) - IFNULL(`order_items_quantity_allocated_pickable_sum`, 0), 0)
                WHERE `products`.`id` = :product_id2',
                [
                    'product_id1' => $product->id,
                    'product_id2' => $product->id
                ]
            );
        }, 10);
    }
}
