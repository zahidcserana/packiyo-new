<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippedItemReportTableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        unset($resource);

        $resource['serial_number'] = $this->serial_number;
        $resource['qty_ordered'] = $this->orderItem->quantity;
        $resource['qty_shipped'] = $this->quantity;
        $resource['price'] = $this->orderItem->price;
        $resource['price_total'] = $this->orderItem->price * $this->orderItem->quantity_shipped;
        $resource['store'] = $this->orderItem->order->orderChannel->name ?? '';
        $resource['shipping_method'] = $this->package->shipment->shippingMethod->name ?? null;
        $resource['shipping_carrier'] = $this->package->shipment->shippingMethod?->shippingCarrier->getNameAndIntegrationAttribute() ?? null;
        $resource['packer'] = $this->package->shipment->user->contactInformation->name ?? '';
        $resource['date_shipped'] = user_date_time($this->package->shipment->created_at, true);
        $resource['tracking_number'] = $this->package->shipment->trackingNumbersLink();
        $resource['location_name'] = $this->location->name;

        $resource['order'] = [
            'id' => $this->orderItem->order->id,
            'number' => $this->orderItem->order->number,
            'url' => route('order.edit', ['order' => $this->orderItem->order]),
            'ordered_at' => user_date_time($this->orderItem->order->ordered_at, true),
        ];

        $resource['tote'] = $this->tote ? [
            'id' => $this->tote->id,
            'name' => $this->tote->name,
            'url' => route('tote.edit', ['tote' => $this->tote]),
        ] : null;

        $resource['lot'] = $this->lot ? [
            'id' => $this->lot->id,
            'name' => $this->lot->name,
            'expiration_date' => user_date_time($this->lot->expiration_date, true),
        ] : null;

        $resource['shippingBox'] = $this->package->shippingBox ? [
            'id' => $this->package->shippingBox->id,
            'name' => $this->package->shippingBox->name,
            'url' => route('shipping_box.edit', ['shipping_box' => $this->package->shippingBox]),
        ] : null;

        $resource['product'] = [
            'id' => $this->orderItem->product_id,
            'sku' => $this->orderItem->sku,
            'name' => $this->orderItem->name,
            'url' => route('product.edit', ['product' => $this->orderItem->product]),
        ];

        $resource['customer'] = [
            'url' => route('customer.edit', ['customer' => $this->orderItem->order->customer]),
            'name' => $this->orderItem->order->customer->contactInformation->name
        ];

        return $resource;
    }
}
