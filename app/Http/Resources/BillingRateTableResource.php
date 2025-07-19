<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BillingRateTableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        unset($resource);

        $resource['id'] = $this->id;
        $resource['name'] = $this->name;
        $resource['description'] = $this->settings['description'] ?? '';
        $resource['updated_at'] = user_date_time($this->updated_at, true);
        $resource['code'] = $this->code;
        $resource['is_enabled'] = $this->is_enabled;
        $resource['link_edit'] =  route('billing_rates.edit', ['billing_rate' => $this->id, 'rate_card' => $this->rate_card_id]);
        $resource['link_delete'] = ['token' => csrf_token(), 'url' => route('billing_rates.destroy', ['billing_rate' => $this->id, 'rate_card' => $this->rate_card_id])];

        return $resource;
    }
}
