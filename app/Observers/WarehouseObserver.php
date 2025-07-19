<?php

namespace App\Observers;

use App\Models\Location;
use App\Models\Warehouse;

class WarehouseObserver
{
    /**
     * Handle the warehouse "saved" event.
     *
     * @param  Warehouse  $warehouse
     * @return void
     */
    public function saved(Warehouse $warehouse): void
    {
        Location::firstOrCreate([
            'warehouse_id' => $warehouse->id,
            'name' => 'Receiving',
            'is_receiving' => true,
            'protected' => true,
        ]);

        Location::firstOrCreate([
            'warehouse_id' => $warehouse->id,
            'name' => Location::PROTECTED_LOC_NAME_RESHIP,
            'protected' => true,
        ]);
    }

    /**
     * Handle the warehouse "deleting" event.
     *
     * @param  Warehouse  $warehouse
     * @return void
     */
    public function deleting(Warehouse $warehouse): void
    {
        $warehouse->productWarehouses()->delete();
    }
}
