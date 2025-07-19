<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPickingTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private $customerId, private $quantity, private $type, private $tagId, private $tagName, private $orderId, private $pickingBatch, private $task, private $excludeSingleLineOrders, private User $user, private $warehouseId = null)
    {
        $this->queue = 'picking';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $orders = app('routeOptimizer')->getValidOrdersToPick($this->customerId, $this->type, $this->tagId, $this->tagName, $this->orderId, $this->excludeSingleLineOrders, $this->warehouseId);

        if ($this->warehouseId) {
            $warehouse = Warehouse::find($this->warehouseId);
        } else {
            $warehouse = Warehouse::where('customer_id', $this->customerId)->first();
        }

        if (count($orders) && $warehouse) {
            app('routeOptimizer')->processPickingBatch($this->quantity, collect($orders), $this->user, $this->pickingBatch, $this->task, $warehouse);
        } else {
            if (count($orders) < 1) {
                Log::channel('picking')->error('There are no valid orders to pick');
            } else if (!$warehouse) {
                Log::channel('picking')->error('No warehouse for the given customer has been selected');
            }
            // we should forcedelete the picking batch and the task but we're not doing it before we agree how it should work across the board
            app('routeOptimizer')->deletePickingBatchAndTask($this->pickingBatch, $this->task);
        }
    }

    public function failed(): void
    {
        Log::channel('picking')->error('Picking task failed, attempting to delete the batch and task');

        // we should forcedelete the picking batch and the task but we're not doing it before we agree how it should work across the board
        app('routeOptimizer')->deletePickingBatchAndTask($this->pickingBatch, $this->task);
    }
}
