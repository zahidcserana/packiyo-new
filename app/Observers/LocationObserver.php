<?php

namespace App\Observers;

use App\Features\MultiWarehouse;
use App\Jobs\AllocateInventoryJob;
use App\Models\Location;
use Laravel\Pennant\Feature;

class LocationObserver
{
    /**
     * Handle the Location "saving" event.
     *
     * @param Location $location
     * @return void
     */
    public function saving(Location $location): void
    {
        if ($location->is_receiving) {
            $location->pickable = $location->pickable_effective = false;
            $location->bulk_ship_pickable_effective = $location->bulk_ship_pickable_effective = false;
        } else {
            if (is_null($location->locationType)) {
                $location->pickable_effective = $location->pickable;
                $location->sellable_effective = $location->sellable;
                $location->bulk_ship_pickable_effective = $location->bulk_ship_pickable;
                $location->disabled_on_picking_app_effective = $location->disabled_on_picking_app;
            } else {
                $location->pickable_effective = $location->locationType->pickable ?? $location->pickable;
                $location->sellable_effective = $location->locationType->sellable ?? $location->sellable;
                $location->bulk_ship_pickable_effective = $location->locationType->bulk_ship_pickable ?? $location->bulk_ship_pickable;
                $location->disabled_on_picking_app_effective = $location->locationType->disabled_on_picking_app ?? $location->disabled_on_picking_app;
            }
        }
    }

    public function saved(Location $location): void
    {
        if ($location->wasChanged(['pickable_effective', 'sellable_effective'])) {
            $locationProducts = $location->locationProducts()->where('location_product.quantity_on_hand', '!=', 0)->get();
            foreach ($locationProducts as $locationProduct) {
                if (Feature::for('instance')->active(MultiWarehouse::class)) {
                    AllocateInventoryJob::dispatch($locationProduct->product, $location->warehouse);
                }  else {
                    AllocateInventoryJob::dispatch($locationProduct->product);
                }
            }
        }
    }
}
