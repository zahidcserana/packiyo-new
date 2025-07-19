<?php

namespace App\Observers;

use App\Jobs\AllocateInventoryJob;
use App\Models\KitItem;
use App\Models\Product;

class KitItemObserver
{
    public function saved(KitItem $kitItem): void
    {
        if ($kitItem->component) {
            $this->allocateComponent($kitItem->component);
        }
    }

    /**
     * Handle the KitItem "deleted" event.
     * @param KitItem $kitItem
     * @return void
     */
    public function deleted(KitItem $kitItem): void
    {
        if ($kitItem->kit) {
            $this->allocateComponent($kitItem->kit);
        }
    }

    /**
     * @param Product $product
     * @return void
     */
    private function allocateComponent(Product $product): void
    {
        AllocateInventoryJob::dispatch($product)->onQueue('allocation-high');
    }
}
