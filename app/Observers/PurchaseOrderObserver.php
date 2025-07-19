<?php

namespace App\Observers;

use App\Models\PurchaseOrder;

class PurchaseOrderObserver
{
    /**
     * Handle the PurchaseOrder "saved" event.
     *
     * @param PurchaseOrder $purchaseOrder
     * @return void
     */
    public function saved(PurchaseOrder $purchaseOrder): void
    {
        foreach ($purchaseOrder->items as $purchaseOrderItem) {
            $purchaseOrderItem->product->calculateQuantityInbound();
        }
    }
}
