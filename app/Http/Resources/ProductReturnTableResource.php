<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductReturnTableResource extends JsonResource
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
        $resource['quantity_orders'] = $this->getQuantityOrders();
        $resource['quantity_requested'] = $this->quantity;
        $resource['quantity_received'] = $this->quantity_received;

        $resource['order'] = [
                'number' => $this->return_->order->number,
                'url' => route('order.edit', ['order' => $this->return_->order])
            ];

        return $resource;
    }
}
