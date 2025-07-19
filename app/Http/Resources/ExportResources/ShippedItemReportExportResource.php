<?php

namespace App\Http\Resources\ExportResources;

use Illuminate\Http\Request;

class ShippedItemReportExportResource extends ExportResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'sku' => $this->orderItem->sku,
            'product_name' => $this->orderItem->name,
            'order_number' => $this->orderItem->order->number,
            'serial_number' => $this->serial_number,
            'qty_ordered' => $this->orderItem->quantity,
            'qty_shipped' => $this->quantity,
            'price' => $this->orderItem->price,
            'price_total' => $this->orderItem->price * $this->orderItem->quantity_shipped,
            'store' => $this->orderItem->order->orderChannel->name ?? '',
            'location' => $this->location->name ?? '',
            'shipping_method' => $this->package->shipment->shippingMethod->name ?? null,
            'shipping_carrier' => $this->package->shipment->shippingMethod?->shippingCarrier->getNameAndIntegrationAttribute() ?? null,
            'packer' => $this->package->shipment->user->contactInformation->name ?? '',
            'date_shipped' => user_date_time($this->package->shipment->created_at, true),
            'tracking_number' => $this->package->shipment->trackingNumbers(),
            'ordered_at' => user_date_time($this->orderItem->order->ordered_at, true),
            'tote' => $this->tote->name ?? '',
            'lot' => $this->lot->name ?? '',
            'lot_expiration' => empty($this->lot->expiration_date) ? null : user_date_time($this->lot->expiration_date, true),
            'shipping_box' => $this->package->shippingBox->name ?? '',
        ];
    }

    public static function columns(): array
    {
        return [
            'SKU',
            'Product name',
            'Order number',
            'Serial number',
            'Qty ordered',
            'Qty shipped',
            'Price',
            'Price total',
            'Store',
            'Location',
            'Shipping method',
            'Shipping carrier',
            'Packer',
            'Date shipped',
            'Tracking #',
            'Order time',
            'Tote',
            'Lot',
            'Lot expiration',
            'Shipping box',
        ];
    }
}
