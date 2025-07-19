<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotInventoryReportTableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        unset($resource);

        $resource['lot_item_id'] = $this->id;
        $resource['on_hand'] = $this->quantity_remaining;
        $resource['lot_value'] = $this->lot->item_price * $this->quantity_remaining;

        $resource['product'] = [
            'url' => route('product.edit', ['product' => $this->lot->product]),
            'name' => $this->lot->product->name,
            'sku' => $this->lot->product->sku
        ];
        $resource['location'] = [
            'id' => $this->location_id,
            'name' => $this->location->name,
            'url' => route('location.edit', ['location' => $this->location]),
        ];
        $resource['lot'] = [
            'id' => $this->lot_id,
            'name' => $this->lot->name,
            'item_price' => $this->lot->item_price,
            'expiration_date' => user_date_time($this->lot->expiration_date, true),
        ];
        $resource['warehouse'] = [
            'id' => $this->location->warehouse->id,
            'name' => $this->location->warehouse->contactInformation->name,
            'url' => route('warehouses.edit', [ 'warehouse' => $this->location->warehouse]),
        ];

        return $resource;
    }
}

