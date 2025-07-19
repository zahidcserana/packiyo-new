<?php

namespace App\Components\Allocation;

use App\Features\AllowNonSellableAllocation;
use App\Features\RequiredReadyToPickForPacking;
use Laravel\Pennant\Feature;
use App\Models\{LocationProduct, OrderItem, Product, ProductWarehouse, Warehouse};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class MultiWarehouseComponent
{
    /**
     * @param Product $product
     * @param Warehouse $warehouse
     * @return void
     */
    public function allocateInventory(Product $product, Warehouse $warehouse): void
    {
        if ($product->isKit()) {
            $quantityOnHand = $this->calculateKitQuantities($product, $warehouse);
            $quantityPickable = $quantityOnHand;
        } else {
            $quantityOnHand = $this->recalculateQuantityOnHand($product, $warehouse);
            $quantityPickable = $this->recalculateQuantityPickable($product, $warehouse);
            $quantityNonSellable = $this->recalculateNonSellableQuantity($product, $warehouse);

            if (Feature::for('instance')->inactive(AllowNonSellableAllocation::class)) {
                $quantityOnHand -= $quantityNonSellable;
            }

            $this->recalculateReservedQuantities($product);
            $this->recalculateInboundQuantities($product, $warehouse);
        }

        $quantityOnHand = max(0, $quantityOnHand);
        $quantityOnHandRemaining = $quantityOnHand;

        $quantityPickableRemaining = $quantityPickable;

        $quantityBackordered = 0;

        $orderItems = $product->orderItem()
            ->select('order_items.*', 'orders.allocation_hold')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('warehouse_id', $warehouse->id)
            ->where(function(Builder $query) {
                $query->where('quantity_pending', '!=', 0)
                    ->orWhere('quantity_allocated', '!=', 0)
                    ->orWhere('quantity_allocated_pickable', '!=', 0)
                    ->orWhere('quantity_backordered', '!=', 0);
            })
            ->orderByDesc('order_items.quantity_in_tote')
            ->orderByDesc('orders.priority_score')
            ->get();

        $orderIdsToReprocess = [];

        foreach ($orderItems as $orderItem) {
            $orderItemQuantityPending = max($orderItem->quantity_pending, 0);

            if ($orderItem->allocation_hold) {
                $orderItemQuantityAllocated = 0;
                $orderItemQuantityAllocatedPickable = 0;
                $orderItemQuantityBackordered = $orderItemQuantityPending;
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

        $this->updateSummedQuantities(
            $product,
            $warehouse,
            $quantityOnHand - $quantityOnHandRemaining,
            $quantityPickable - $quantityPickableRemaining,
            $quantityBackordered,
            ($quantityOnHand - $quantityOnHandRemaining) - ($quantityPickable - $quantityPickableRemaining)
        );

        foreach ($product->kitParents as $kitParent) {
            $this->allocateInventory($kitParent, $warehouse);
        }
    }

    /**
     * Calculate on hand for regular products
     *
     * @param Product $product
     * @param Warehouse $warehouse
     * @return mixed
     */
    public function recalculateQuantityOnHand(Product $product, Warehouse $warehouse)
    {
        $quantityOnHand = LocationProduct::join('locations', 'location_product.location_id', '=', 'locations.id')
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->sum('quantity_on_hand');

        ProductWarehouse::updateOrCreate([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id
        ], [
            'quantity_on_hand' => $quantityOnHand
        ]);

        $product->update([
            'quantity_on_hand' => $product
                ->productWarehouses()
                ->sum('quantity_on_hand')
        ]);

        return $quantityOnHand;
    }

    /**
     * @param Product $product
     * @param Warehouse $warehouse
     * @return mixed
     */
    public function recalculateQuantityPickable(Product $product, Warehouse $warehouse)
    {
        $quantityPickable = LocationProduct::join('locations', 'location_product.location_id', '=', 'locations.id')
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('locations.sellable_effective', 1)
            ->where('locations.pickable_effective', 1)
            ->when(
                Feature::for('instance')->inactive(RequiredReadyToPickForPacking::class),
                fn (Builder $builder) => $builder->where('locations.disabled_on_picking_app_effective', 0)
            )
            ->sum('quantity_on_hand');

        ProductWarehouse::updateOrCreate([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id
        ], [
            'quantity_pickable' => $quantityPickable
        ]);

        $product->update([
            'quantity_pickable' => $product
                ->productWarehouses()
                ->sum('quantity_pickable')
        ]);

        return $quantityPickable;
    }

    /**
     * @param Product $product
     * @param Warehouse $warehouse
     * @return int
     */
    public function getSellAheadQuantities(Product $product, Warehouse $warehouse): int
    {
        $quantitySellAhead = $product->purchaseOrderLine()
            ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->where('warehouse_id', $warehouse->id)
            ->sum('quantity_sell_ahead');

        ProductWarehouse::updateOrCreate([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id
        ], [
            'quantity_sell_ahead' => $quantitySellAhead
        ]);

        $product->update([
            'quantity_sell_ahead' => $product
                ->productWarehouses()
                ->sum('quantity_sell_ahead')
        ]);

        return $quantitySellAhead;
    }

    /**
     * @param Product $product
     * @return void
     */
    private function recalculateReservedQuantities(Product $product): void
    {
        $product->update([
            'quantity_reserved' => $product
                ->productWarehouses()
                ->sum('quantity_reserved')
        ]);
    }

    /**
     * @param Product $product
     * @param Warehouse $warehouse
     * @return void
     */
    private function recalculateInboundQuantities(Product $product, Warehouse $warehouse): void
    {
        $quantity = 0;
        $quantityReceived = 0;

        foreach ($product->purchaseOrderLineActive()->where('purchase_orders.warehouse_id', $warehouse->id)->get() as $poLine) {
            $quantity += $poLine->quantity;
            $quantityReceived += $poLine->quantity_received;
        }

        $inboundQuantity = $quantity - $quantityReceived;

        ProductWarehouse::updateOrCreate([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id
        ], [
            'quantity_inbound' => $inboundQuantity
        ]);

        $product->update([
            'quantity_inbound' => $product
                ->productWarehouses()
                ->sum('quantity_inbound')
        ]);
    }

    /**
     * Calculate non sellable quantity for products
     *
     * @param Product $product
     * @param Warehouse $warehouse
     * @return int
     */
    public function recalculateNonSellableQuantity(Product $product, Warehouse $warehouse): int
    {
        $quantityNonSellable = LocationProduct::join('locations', 'location_product.location_id', '=', 'locations.id')
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('locations.sellable_effective', 0)
            ->sum('quantity_on_hand');

        ProductWarehouse::updateOrCreate([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id
        ], [
            'quantity_non_sellable' => $quantityNonSellable
        ]);

        $product->update([
            'quantity_non_sellable' => $product
                ->productWarehouses()
                ->sum('quantity_non_sellable')
        ]);

        return $quantityNonSellable;
    }

    /**
     * Kit parent on hand is calculated based on available on hand of the components. Additionally, we increase kit
     * parent on hand by the calculated allocated quantities of the components so the available is calculated correctly.
     *
     * @param Product $product
     * @param Warehouse $warehouse
     * @return int
     */
    private function calculateKitQuantities(Product $product, Warehouse $warehouse): int
    {
        $warehouseQuantities = [
            'quantities_available' => [],
            'quantities_allocated' => []
        ];

        $componentIds = [];

        foreach ($product->kitItems as $component) {
            $componentQuantity = $component->pivot->quantity ?: 1;

            if (!$componentQuantity) {
                continue;
            }

            $productWarehouse = $component->productWarehouses->where('warehouse_id', $warehouse->id)->first();

            $componentQuantityAllocated = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('product_id', $component->id)
                ->whereHas('parentOrderItem', function($query) use ($product) {
                    $query->where('product_id', $product->id);
                })
                ->where('orders.warehouse_id', $warehouse->id)
                ->sum('quantity_allocated');

            $warehouseQuantities['quantities_available'][$component->id] = intdiv($productWarehouse->quantity_available ?? 0, $componentQuantity);
            $warehouseQuantities['quantities_allocated'][$component->id] = intdiv($componentQuantityAllocated, $componentQuantity);

            $componentIds[$component->id] = $component->id;
        }

        foreach ($componentIds as $componentId) {
            if (!Arr::has($warehouseQuantities['quantities_available'], $componentId)) {
                $warehouseQuantities['quantities_available'][$componentId] = 0;
            }

            if (!Arr::has($warehouseQuantities['quantities_allocated'], $componentId)) {
                $warehouseQuantities['quantities_allocated'][$componentId] = 0;
            }
        }

        $productWarehouseQuantityAvailable = empty($warehouseQuantities['quantities_available']) ? 0 : min($warehouseQuantities['quantities_available']);
        $productWarehouseQuantityAllocated = empty($warehouseQuantities['quantities_allocated']) ? 0 : min($warehouseQuantities['quantities_allocated']);
        $productWarehouseQuantityOnHand = $productWarehouseQuantityAvailable + $productWarehouseQuantityAllocated;

        ProductWarehouse::updateOrCreate([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id
        ], [
            'quantity_on_hand' => $productWarehouseQuantityOnHand,
            'quantity_pickable' => $productWarehouseQuantityOnHand
        ]);

        $product->quantity_on_hand = $product
            ->productWarehouses()
            ->sum('quantity_on_hand');

        $product->quantity_pickable = $product->quantity_on_hand;

        $product->saveQuietly();

        if ($product->wasChanged('quantity_on_hand')) {
            app('inventoryLog')->triggerAdjustInventoryWebhook($product);
        }

        return $product->quantity_on_hand;
    }

    /**
     * @param Product $product
     * @param Warehouse $warehouse
     * @param $quantityAllocated
     * @param $quantityAllocatedPickable
     * @param $quantityBackordered
     * @param $quantityToReplenish
     * @return void
     */
    private function updateSummedQuantities(
        Product $product,
        Warehouse $warehouse,
        $quantityAllocated,
        $quantityAllocatedPickable,
        $quantityBackordered,
        $quantityToReplenish
    ): void
    {
        $productWarehouse = ProductWarehouse::firstOrNew([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id
        ]);

        $quantityNonSellable = LocationProduct::join('locations', 'location_product.location_id', '=', 'locations.id')
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('locations.sellable_effective', 0)
            ->sum('quantity_on_hand');

        $productWarehouse->fill([
            'quantity_allocated' => $quantityAllocated,
            'quantity_allocated_pickable' => $quantityAllocatedPickable,
            'quantity_backordered' => $quantityBackordered,
            'quantity_to_replenish' => $quantityToReplenish,
        ]);

        $productWarehouse->fill([
            'quantity_available' => $productWarehouse->quantity_on_hand - $quantityNonSellable - $productWarehouse->quantity_allocated + $this->getSellAheadQuantities($product, $warehouse) - $productWarehouse->quantity_backordered + $productWarehouse->quantity_reserved
        ]);

        $productWarehouse->save();

        $productWarehouses = $product->productWarehouses()->get();

        $product->update([
            'quantity_allocated' => $productWarehouses->sum('quantity_allocated'),
            'quantity_allocated_pickable' => $productWarehouses->sum('quantity_allocated_pickable'),
            'quantity_backordered' => $productWarehouses->sum('quantity_backordered'),
            'quantity_to_replenish' => $productWarehouses->sum('quantity_to_replenish'),
            'quantity_available' => $productWarehouses->sum('quantity_available'),
            'quantity_inbound' => $productWarehouses->sum('quantity_inbound')
        ]);
    }
}
