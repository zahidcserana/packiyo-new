<?php

namespace App\Http\Resources;

use App\Models\Shipment;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BulkShippingTableResource extends JsonResource
{
    public function toArray($request)
    {
        unset($resource);

        $firstOrder = $this->resource->firstBulkShipBatchOrder()->first()->order ?? null;

        if (!$firstOrder) {
            $firstOrder = $this->resource->suggestedOrdersQuery()->first();
        }

        if ($firstOrder) {
            $orderItems = $firstOrder?->orderItems()
                ->where('quantity_allocated', '>', 0)
                ->get()
                ->map(function ($orderItem) {
                    return [
                        'image' => $orderItem->product->productImages->first()->source ?? asset('img/no-image.png'),
                        'quantity' => $orderItem->quantity_allocated,
                        'sku' => $orderItem->sku,
                        'name' => $orderItem->name,
                    ];
                })->toArray();
        }

        $resource['id'] = $this->id;
        $resource['created_at'] = user_date_time($this->created_at, true);
        $resource['updated_at'] = user_date_time($this->updated_at, true);
        $resource['shipped_at'] = user_date_time($this->shipped_at, true);
        $resource['order_items'] = $orderItems ?? [];
        $resource['batch_key'] = $this->batch_key;
        $resource['total_orders'] = $this->total_orders;
        $resource['total_items'] = $this->total_items * $this->total_orders;
        $resource['label_pdf'] = Storage::url($this->label);
        $resource['bulk_ship_shipping_page_url'] = route('bulk_shipping.shipping', $this);
        $resource['mark_as_printed_url'] = route('bulk_shipping.markAsPrinted', $this);
        $resource['mark_as_packed_url'] = route('bulk_shipping.markAsPacked', $this);
        $resource['unlock_url'] = route('bulk_shipping.unlock', $this);
        $resource['close_bulk_ship_batch'] = ['token' => csrf_token(), 'url' => route('bulk_shipping.closeBulkShipBatch', $this)];
        $resource['in_progress'] = $this->in_progress;
        $resource['labels'] = [
            [
                'url' => Storage::url($this->label),
                'name' => 'Bulk label'
            ]
        ];

        if (!$this->in_progress) {
            foreach ($this->orders as $order) {
                if (!$order->pivot->labels_merged && $order->pivot->shipment_id) {
                    $shipment = Shipment::find($order->pivot->shipment_id);

                    if ($shipment) {
                        foreach ($shipment->shipmentLabels ?? [] as $shipmentLabel) {
                            $resource['labels'][] = [
                                'url' => route('shipment.label', [
                                    'shipment' => $shipment,
                                    'shipmentLabel' => $shipmentLabel
                                ]),
                                'name' => 'Single label for order ' . $order->number
                            ];
                        }
                    }
                }
            }
        }

        if ($this->printedUser) {
            $resource['printed_by'] = $this->printedUser->contactInformation->name . ' | ' . $this->printed_at;
        }

        if ($this->packedUser) {
            $resource['packed_by'] = $this->packedUser->contactInformation->name . ' | ' . $this->packed_at;
        }

        if ($this->lockTask) {
            $resource['locked_by'] = $this->lockTask->user->contactInformation->name ?? '';
            $resource['can_unlock'] = auth()->user()->isAdmin() || auth()->user()->id == $this->lockTask->user_id;
        }

        return $resource;
    }
}
