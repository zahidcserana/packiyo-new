<?php

namespace App\Jobs\DataWarehouse;

use App\DataWarehouseRecords\ShipmentRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ShipmentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Arrayable|array $shipments;
    /**
     * Send Customer statistics data to the data warehouse
     *
     * @return void
     */
    public function __construct($shipments)
    {
        $this->queue = 'data-warehouse';
        $this->shipments = $shipments;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        (new ShipmentRecord())->push($this->shipments);
    }
}
