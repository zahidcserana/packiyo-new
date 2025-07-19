<?php

namespace App\Jobs\DataWarehouse;

use App\DataWarehouseRecords\CustomerStatsRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CustomerStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Send Customer statistics data to the data warehouse
     *
     * @return void
     */
    public function __construct()
    {
        $this->queue = 'data-warehouse';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        (new CustomerStatsRecord())->push();
    }
}
