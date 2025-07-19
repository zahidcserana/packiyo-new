<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationTableResource extends JsonResource
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
        $resource['location_name'] = $this->name;
        $resource['location_pickable'] = $this->is_pickable_label;
        $resource['location_sellable'] = $this->is_sellable_label;
        $resource['location_disabled_on_picking_app'] = $this->is_disabled_on_picking_app_label;
        $resource['location_bulk_ship_pickable'] = $this->is_bulk_ship_pickable_label;
        $resource['location_types'] = !is_null($this->locationType) ? $this->locationType->name : __('Not set');
        $resource['location_protected'] = $this->protected;
        $resource['warehouse_id'] = $this->warehouse->id;
        $resource['warehouse_name'] = $this->warehouse->contactInformation['name'];
        $resource['warehouse_url'] = route('warehouses.edit', [ 'warehouse' => $this->warehouse]);
        $resource['audit'] = route('location.audit', [ 'location' => $this]);
        $resource['warehouse_address'] = $this->warehouse->contactInformation->address;
        $resource['warehouse_zip'] = $this->warehouse->contactInformation->zip;
        $resource['warehouse_city'] = $this->warehouse->contactInformation->zip;
        $resource['warehouse_email'] = $this->warehouse->contactInformation->email;
        $resource['warehouse_phone'] = $this->warehouse->contactInformation->phone;
        $resource['link_delete'] = ['token' => csrf_token(), 'url' => route('location.destroy', ['location' => $this])];
        $resource['location_receiving'] = $this->is_receiving ? __('Yes') : __('No');

        return $resource;
    }
}
