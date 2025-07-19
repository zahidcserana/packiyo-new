<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductLotLocationsDataTableResource extends JsonResource
{
    public function toArray($request): array
    {
        $resource['id'] = $this->id;
        $resource['key'] = $this->key;
        $resource['name'] = $this->name . ' (' . $this->warehouse->contactInformation->name . ')';
        $resource['quantity'] = $this->quantity_remaining ?? $this->quantity_on_hand;
        $resource['quantity_reserved_for_picking'] = $this->quantity_reserved_for_picking;
        $resource['lot_name'] = $this->lot_name;
        $resource['lot_id'] = $this->lot_id;
        $resource['lot_expiration'] = $this->expiration_date ? user_date_time($this->expiration_date) : __('N/A');
        $resource['lot_vendor'] = $this->supplier_name;
        $resource['lot_item_id'] = $this->lot_item_id;
        $resource['location_pickable'] = $this->is_pickable_label;
        $resource['location_sellable'] = $this->is_sellable_label;

        return $resource;
    }
}
