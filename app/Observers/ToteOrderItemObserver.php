<?php

namespace App\Observers;

use App\Models\LocationProduct;
use App\Models\PickingBatchItem;
use App\Models\ToteOrderItem;

class ToteOrderItemObserver
{
    /**
     * Handle the ToteOrderItem "saving" event.
     *
     * @param  \App\Models\ToteOrderItem  $toteOrderItem
     * @return void
     */
    public function saving(ToteOrderItem $toteOrderItem)
    {
        if (is_null($toteOrderItem->quantity_remaining) && $toteOrderItem->quantity) {
            $toteOrderItem->quantity_remaining = $toteOrderItem->quantity;
        }
    }

    /**
     * Handle the ToteOrderItem "saved" event.
     *
     * @param  \App\Models\ToteOrderItem  $toteOrderItem
     * @return void
     */
    public function saved(ToteOrderItem $toteOrderItem)
    {
        $pickingBatchItem = PickingBatchItem::with('orderItem')->where('id', $toteOrderItem->picking_batch_item_id)->first();

        if ($pickingBatchItem) {
            $pickingBatchItem->save();
        }

        $toteOrderItem->orderItem->update([
            'quantity_in_tote' => ToteOrderItem::where('order_item_id', $toteOrderItem->order_item_id)->sum('quantity_remaining')
        ]);
    }

    /**
     * Handle the ToteOrderItem "deleted" event.
     *
     * @param  \App\Models\ToteOrderItem  $toteOrderItem
     * @return void
     */
    public function deleted(ToteOrderItem $toteOrderItem)
    {
        $this->saved($toteOrderItem);
    }
}
