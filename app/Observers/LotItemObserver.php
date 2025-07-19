<?php

namespace App\Observers;

use App\Models\LotItem;

class LotItemObserver
{
    public function saving(LotItem $lotItem) : void
    {
        $lotItem->quantity_remaining = $lotItem->quantity_added - $lotItem->quantity_removed;

        if (!$lotItem->product_id) {
            $lotItem->product_id = $lotItem->lot->product_id;
        }
    }
}
