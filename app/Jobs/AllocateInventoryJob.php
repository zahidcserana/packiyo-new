<?php

namespace App\Jobs;

use App\Features\MultiWarehouse;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Laravel\Pennant\Feature;

class AllocateInventoryJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Product $product;
    public ?Warehouse $warehouse = null;

    public int $uniqueFor = 60 * 15;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Product $product, ?Warehouse $warehouse = null)
    {
        $this->queue = 'allocation';

        $this->product = $product;
        $this->warehouse = $warehouse;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            if (Feature::for('instance')->active(MultiWarehouse::class)) {
                if ($this->warehouse) {
                    app('allocation')->allocateInventory($this->product, $this->warehouse);
                } else {
                    $warehouses = $this->product->customer->parent->warehouses ?? $this->product->customer->warehouses;

                    foreach ($warehouses as $warehouse) {
                        app('allocation')->allocateInventory($this->product, $warehouse);
                    }
                }

            } else {
                app('allocation')->allocateInventory($this->product);
            }
        } catch (\Exception $exception) {
            Log::error('Allocation error: ' . $exception->getMessage());
        }
    }

    public function uniqueId()
    {
        return $this->queue . '-' . $this->product->id . '-' . ($this->warehouse->id ?? 'primary');
    }
}
