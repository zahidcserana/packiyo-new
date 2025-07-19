<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use App\Models\RateCard;

class RateCardTableResource extends JsonResource
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

        $resource['name'] = $this->name;
        $resource['monthly_cost'] = $this->monthly_cost;
        $resource['updated_at'] = user_date_time($this->updated_at, true);
        $resource['per_user_cost'] = $this->per_user_cost;
        $resource['per_purchase_order_received_cost'] = $this->per_purchase_order_received_cost;
        $resource['per_product_cost'] = $this->per_product_cost;
        $resource['per_shipment_cost'] = $this->per_shipment_cost;
        $resource['per_return_cost'] = $this->per_return_cost;
        $resource['is_readonly_user'] = false;
        $resource['link_edit'] = route('rate_cards.edit', ['rate_card' => $this]);
        $resource['link_delete'] = ['token' => csrf_token(), 'url' => route('rate_cards.destroy', ['id' => $this->id, 'rate_card' => $this])];
        $resource['link_clone'] = ['token' => csrf_token(), 'url' => route('rate_cards.clone', ['id' => $this->id])];


        return $resource;
    }
}
