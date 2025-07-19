<?php

namespace App\Http\Resources\ExportResources;

use Illuminate\Http\Request;

class ReturnExportResource extends ExportResource
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
        $trackingNumbers = '';

        foreach($this->returnTrackings as $tracking) {
            $trackingNumbers .= $tracking->tracking_number . "\r\n";
        }

        if (isset($this->items)) {
            foreach ($this->items as $item) {
                $resource[] = [
                    'number' => $this->number,
                    'order' => $this->order->number,
                    'status' => $this->getStatusText(),
                    'sku' => $item->product->sku,
                    'quantity' => $item->quantity,
                    'quantity_received' => $item->quantity_received,
                    'reason' => strip_tags($this->reason),
                    'warehouse' => $this->warehouse->contactInformation->name,
                    'created_at' => user_date_time($this->created_at),
                    'city' => $this->order->shippingContactInformation->city,
                    'zip' => $this->order->shippingContactInformation->zip,
                    'tracking_number' => rtrim($trackingNumbers),
                    'tags' => $this->tags->pluck('name')->join(',')
                ];
            }
        }

        return $resource;
    }

    public static function columns(): array
    {
        return [
            'number',
            'order',
            'status',
            'sku',
            'quantity',
            'quantity_received',
            'reason',
            'warehouse',
            'created_at',
            'city',
            'zip',
            'tracking_number',
            'tags'
        ];
    }
}
