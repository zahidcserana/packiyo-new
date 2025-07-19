<?php

namespace App\Http\Resources;

use App\Models\ShippingMethodMapping;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingMethodMappingTableResource extends JsonResource
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

        if (!empty($this->mappedShippingMethod)) {
            if (array_key_exists($this->mappedShippingMethod->type, ShippingMethodMapping::CHEAPEST_SHIPPING_METHODS)) {
                $shippingMethodName = ShippingMethodMapping::CHEAPEST_SHIPPING_METHODS[$this->mappedShippingMethod->type];
            } else {
                $shippingMethodName = $this->mappedShippingMethod->shippingMethod->name ?? '';
            }
        } else {
            $shippingMethodName = '';
        }

        $resource['id'] = $this->id;
        $resource['shipping_method_name'] = $this->shipping_method_name;
        $resource['is_mapped'] = empty($this->mappedShippingMethod) ? __('No') : __('Yes');
        $resource['method_name'] = $shippingMethodName;
        $resource['carrier_name'] = empty($this->mappedShippingMethod) ? '' : $this->mappedShippingMethod->shippingMethod?->shippingCarrier->getNameAndIntegrationAttribute() ?? $shippingMethodName;
        $resource['return_method_name'] = empty($this->mappedShippingMethod) ? '' : $this->mappedShippingMethod->returnShippingMethod->name ?? '';
        $resource['return_carrier_name'] = empty($this->mappedShippingMethod) ? '' : $this->mappedShippingMethod->returnShippingMethod?->shippingCarrier->getNameAndIntegrationAttribute() ?? '';
        $resource['link_edit'] = $this->getEditLink();

        $resource['link_delete'] = empty($this->mappedShippingMethod) ? null : [
            'token' => csrf_token(),
            'url' => route('shipping_method_mapping.destroy', [
                'id' => $this->mappedShippingMethod->id,
                'shipping_method_mapping' => $this->mappedShippingMethod
            ])
        ];

        return $resource;
    }

    private function getEditLink(): string
    {
        if ($this->mappedShippingMethod) {
            return route('shipping_method_mapping.edit', [
                'shipping_method_mapping' => $this->mappedShippingMethod
            ]);
        }

        return route('shipping_method_mapping.create', [
            'shipping_method_name' => base64_encode($this->shipping_method_name)
        ]);
    }
}
