<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductLocationTableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        unset($resource);

        $resource['id'] = $this->id;
        $resource['product_id'] = $this->product->id;
        $resource['location_id'] = $this->location->id;
        $resource['location_product_id'] = $this->location_product_id;
        $resource['location'] = $this->location->name;
        $resource['warehouse'] = $this->location->warehouse->contactInformation['name'];
        $resource['sku'] = $this->product->sku;
        $resource['product_name'] = $this->product->name;
        $resource['quantity'] = $this->lot_item_quantity_remaining ?? $this->quantity_on_hand;
        $resource['lot_name'] = $this->lot_name;
        $resource['lot_id'] = $this->lot_id;
        $resource['lot_item_id'] = $this->lot_item_id;
        $resource['lot_expiration_date'] = $this->lot_expiration_date ? user_date_time($this->lot_expiration_date) : '';
        $resource['location_pickable'] = $this->location->is_pickable_label;
        $resource['location_sellable'] = $this->location->is_sellable_label;

        return $resource;
    }
}
