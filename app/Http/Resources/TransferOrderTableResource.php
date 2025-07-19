<?php

namespace App\Http\Resources;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferOrderTableResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        unset($resource);

        $resource['id'] = $this->id;
        $resource['number'] = $this->number;
        $resource['ordered_at'] = user_date_time($this->ordered_at, true);
        $resource['status'] = $this->transferOrderStatus();
        $resource['from_warehouse'] = $this->warehouse->contactInformation->name;
        $resource['to_warehouse'] = $this->shippingContactInformation->name;
        $resource['items_ordered'] = $this->itemsOrdered();
        $resource['items_received'] = $this->itemsReceived();
        $resource['link_edit'] = route('order.edit', ['order' => $this->id ]);
        $resource['link_receive'] = route('purchase_order.receive', ['purchaseOrder' => $this->purchaseOrder->id ]);
        $resource['link_close_po'] = ['token' => csrf_token(), 'url' => route('transfer_orders.close', ['id' => $this->purchaseOrder->id, 'purchaseOrder' => $this->purchaseOrder])];
        $resource['is_client'] = app('user')->isClientCustomer();

        return $resource;
    }

    private function transferOrderStatus(): string
    {
        if (is_null($this->cancelled_at) && is_null($this->fulfilled_at)){
            return __('Created');
        } else if (is_null($this->cancelled_at) && !is_null($this->fulfilled_at) && is_null($this->purchaseOrder->received_at)) {
            return __('Shipped');
        } else if (!is_null($this->purchaseOrder->closed_at)) {
            return __('Closed');
        } else if (!is_null($this->purchaseOrder->received_at)) {
            return __('Received');
        }

        return '';
    }

    private function itemsOrdered(): int
    {
        return $this->orderItems->sum('quantity');
    }

    private function itemsReceived(): int
    {
        return $this->purchaseOrder->purchaseOrderItems->sum('quantity_received');
    }
}
