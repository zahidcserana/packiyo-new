<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class BulkShipBatchOrderTableResource extends JsonResource
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

        $shippingMethods = collect($this->customer->shippingMethods);
        if ($this->customer->parent_id) {
            $shippingMethods = $shippingMethods->merge($this->customer->parent->shippingMethods);
        }

        if (!$this->pivot?->shipment_id) {
            $shippingMethods =
                ['generic' => __('Generic')]
                + (config('app.env') === 'local' ? ['fail' => __('Fail')] : [])
                + $shippingMethods->pluck('carrierNameAndName', 'id')->all();
        } else {
            $shippingMethod = $this->batch_shipping_method_id ? Arr::get(
                $shippingMethods->pluck('carrierNameAndName', 'id')->all(),
                $this->shipping_method_id,
                __('Generic')
            ) : __('Generic');
        }

        $resource['id'] = $this->id;
        $resource['order_number'] = $this->number;
        $resource['shipping_method'] = $shippingMethod ?? null;
        $resource['shipping_method_id'] = $this->shipping_method_id ?? null;
        $resource['shipping_methods'] = $shippingMethods ?? null;
        $resource['shipment_id'] = $this->pivot?->shipment_id ?? null;
        $resource['status_message'] = $this->pivot?->status_message ?? null;
        $resource['batch_id'] = $this->batch_id;

        return $resource;
    }
}
