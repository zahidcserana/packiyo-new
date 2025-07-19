<?php

namespace App\Listeners;

use App\Events\OrderShippedEvent;
use App\Features\DataWarehousing;
use App\Jobs\DataWarehouse\ShipmentsJob;
use Laravel\Pennant\Feature;

class DataWarehouseListener
{
    public function handle(OrderShippedEvent $event): void
    {
        if (Feature::for('instance')->active(DataWarehousing::class)) {
            dispatch_sync(new ShipmentsJob($event->getShipments()));
        }
    }
}
