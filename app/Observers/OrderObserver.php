<?php

namespace App\Observers;

use App\Features\MultiWarehouse;
use App\Jobs\AllocateInventoryJob;
use App\Models\CustomerSetting;
use App\Models\Order;
use App\Models\Warehouse;
use Laravel\Pennant\Feature;

class OrderObserver
{
    /**
     * Handle the order "saving" event.
     *
     * @param Order $order
     * @return void
     */
    public function saving(Order $order): void
    {
        $order->recalculateWeight();

        app('order')->updatePriorityScore($order);

        if (!$order->wasRecentlyCreated) {
            app('order')->recalculateStatus($order);
            app('order')->recalculateTotals($order);
        }
    }

    /**
     * @param Order $order
     * @return void
     */
    public function saved(Order $order): void
    {
        app('order')->updateSummedQuantitiesV2([$order->id]);

        if (Feature::for('instance')->active(MultiWarehouse::class)) {
            if ($order->wasChanged('warehouse_id')) {
                $oldWarehouseId = $order->getOriginal('warehouse_id');

                $oldWarehouse = Warehouse::find($oldWarehouseId);
                $newWarehouse = Warehouse::find($order->warehouse_id);

                foreach ($order->orderItems->unique('product_id') as $orderItem) {
                    if ($orderItem->product) {
                        if ($oldWarehouse) {
                            AllocateInventoryJob::dispatch($orderItem->product, $oldWarehouse);
                        }
                        AllocateInventoryJob::dispatch($orderItem->product, $newWarehouse);
                    }
                }
            }
        }

        Order::enableAuditing();
    }

    /**
     * @param Order $order
     * @return void
     */
    public function creating(Order $order): void
    {
        if (empty($order->shipping_box_id)) {
            $shippingBoxId = customer_settings($order->customer_id, CustomerSetting::CUSTOMER_SETTING_SHIPPING_BOX_ID);

            if (empty($shippingBoxId) && $order->customer->parent) {
                $shippingBoxId = customer_settings($order->customer->parent->id, CustomerSetting::CUSTOMER_SETTING_SHIPPING_BOX_ID);
            }

            if ($shippingBoxId) {
                $order->shipping_box_id = $shippingBoxId;
            }
        }

        if (!$order->warehouse_id) {
            $warehouseId = customer_settings($order->customer->id, CustomerSetting::CUSTOMER_SETTING_DEFAULT_WAREHOUSE);

            if (empty($warehouseId) && $order->customer->is3plChild()) {
                $warehouseId = customer_settings($order->customer->parent->id, CustomerSetting::CUSTOMER_SETTING_DEFAULT_WAREHOUSE);
            }

            if (empty($warehouseId)) {
                $warehouse = app('warehouse')->filterWarehouses($order->customer)->first();
                $warehouseId = $warehouse->id ?? null;
            }

            if (!empty($warehouseId)) {
                $order->warehouse_id = $warehouseId;
            }
        }
    }
}
