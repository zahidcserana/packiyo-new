<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShippingCarrierTableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        unset($resource);

        $resource['id'] = $this->id;
        $resource['name'] = $this->name;
        $resource['created_at'] = user_date_time($this->created_at, true);
        $resource['updated_at'] = user_date_time($this->updated_at, true);
        $resource['carrier_account'] = $this->carrier_account;
        $resource['active'] = $this->active;
        $resource['integration'] = $this->integration;
        $resource['link_edit'] = empty($this->settings['external_dataflow_id']) ? null : route('shipping_carrier.edit', ['shipping_carrier' => $this]);
        $resource['link_delete'] = ['token' => csrf_token(), 'url' => route('shipping_carrier.destroy', ['id' => $this->id, 'shipping_carrier' => $this])];

        return $resource;
    }
}
