<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAndShippingBoxTableResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $resource = [];
        unset($resource);
        $resource['id'] = $this->id;
        $resource['name'] = sprintf('%s - Shipping Boxes',$this->contactInformation()->first()->name);
        $resource['subSelectables'] = $this->shippingBoxes()->select(['id', 'name'])->get();

        return $resource;

    }
}
