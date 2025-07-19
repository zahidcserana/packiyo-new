<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderTableResource extends JsonResource
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
        $resource['customer'] = ['url' =>route('customer.edit', ['customer' => $this->customer]), 'name' => $this->customer->contactInformation->name];
        $resource['order_channel_name'] = $this->orderChannel->name ?? '';
        $resource['order_slip_url'] = route('order.getOrderSlip', $this);
        $resource['order_status_name'] = $this->getStatusText();
        $resource['shipping_name'] = $this->shippingContactInformation->name;
        $resource['shipping_address'] = $this->shippingContactInformation->address;
        $resource['shipping_city'] = $this->shippingContactInformation->city;
        $resource['shipping_state'] = $this->shippingContactInformation->state;
        $resource['shipping_zip'] = $this->shippingContactInformation->zip;
        $resource['shipping_country'] = $this->shippingContactInformation->country->name ?? '';
        $resource['shipping_email'] = $this->shippingContactInformation->email;
        $resource['shipping_phone'] = $this->shippingContactInformation->phone;
        $resource['priority'] = $this->priority ? __('YES') : __('NO');
        $resource['priority_score'] = $this->priority_score;
        $resource['link_edit'] = route('order.edit', ['order' => $this->id ]);
        $resource['link_delete'] = ['token' => csrf_token(), 'url' => route('order.destroy', ['id' => $this->id, 'order' => $this])];
        $resource['ready_to_ship'] = $this->ready_to_ship ? __('YES') : __('NO');
        $resource['ready_to_pick'] = $this->ready_to_pick ? __('YES') : __('NO');
        $resource['allow_partial'] = $this->allow_partial ? __('YES') : __('NO');
        $resource['tote'] = $this->getTote();
        $resource['ordered_at'] = user_date_time($this->ordered_at, true);
        $resource['order_status_color'] = $this->orderStatus->color ?? null;
        $resource['tags'] = $this->tags->pluck('name')->join(', ');
        $resource['hold_until'] = $this->hold_until ? user_date_time($this->hold_until) : '';
        $resource['ship_before'] = $this->ship_before ? user_date_time($this->ship_before) : '';
        $resource['archived_at'] = $this->archived_at ? user_date_time($this->archived_at, true) : '';
        $resource['is_archived'] = $this->is_archived ? __('YES') : __('NO');
        $resource['disabled_on_picking_app'] =  $this->disabled_on_picking_app ? __('YES') : __('NO');
        $resource['warehouse'] = $this->warehouse->contactInformation->name ?? '';
        $resource['shipping_method'] = $this->shippingMethod->name ?? $this->shipping_method_name ?? 'Generic';
        $resource['locked'] = $this->orderLock ? __('Locked') : __('Not Locked');

        return $resource;
    }

    private function getTote(): ?array
    {
        foreach ($this->orderItems as $orderItem) {
            if (!empty($orderItem->tote())) {
                return ['url' => route('tote.edit', ['tote' => $orderItem->tote()]), 'name' => empty($orderItem->tote()->name) ? 'Unknown' : $orderItem->tote()->name];
            }
        }

        return null;
    }
}
