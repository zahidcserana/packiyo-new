<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $resource = parent::toArray($request);

        $resource['product'] = new ProductResource($this->product);

        $resource['location'] = $this->product ? new LocationCollection($this->product->locations) : null;

        unset($resource['product']['location']);
        unset($resource['product_id']);

        return $resource;
    }
}
