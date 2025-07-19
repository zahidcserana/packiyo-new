<?php

namespace App\Observers;

use App\Models\Lot;

class LotObserver
{
    public function saving(Lot $lot): void
    {
        $lot->item_price = empty($lot->item_price) ? $lot->product->price : $lot->item_price;
    }
}
