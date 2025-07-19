<?php

namespace App\Jobs\Order;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RecalculateReadyToShipOrders implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $orderIds;

    public int $uniqueFor = 60 * 15;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($orderIds = [])
    {
        $this->orderIds = $orderIds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        app('order')->recalculateReadyToShipOrders($this->orderIds);
    }

    public function uniqueId()
    {
        return implode(',', $this->orderIds);
    }
}
