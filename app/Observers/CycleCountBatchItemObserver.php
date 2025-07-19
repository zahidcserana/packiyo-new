<?php

namespace App\Observers;

use App\Models\CycleCountBatchItem;
use Carbon\Carbon;

class CycleCountBatchItemObserver
{
    /**
     * Handle the CycleCountBatchItem "deleted" event.
     *
     * @param  CycleCountBatchItem  $cycleCountBatchItem
     * @return void
     */
    public function deleted(CycleCountBatchItem $cycleCountBatchItem): void
    {
        $cycleCountBatch = $cycleCountBatchItem->CycleCountBatch;

        if ($cycleCountBatch->CycleCountBatchItems()->whereNull('quantity_confirmed')->exists()) {
            return;
        }

        $cycleCountBatch->tasks()->update(['completed_at' => Carbon::now()]);
    }
}
