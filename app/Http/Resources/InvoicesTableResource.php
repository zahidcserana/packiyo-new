<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoicesTableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        unset($resource);

        $resource['id'] = $this->id;
        $resource['customer'] = ['visible' => session('customer_id') == $this->customer->id ? false : true, 'url' =>route('customers.edit', ['customer' => $this->customer]), 'name' => $this->customer->contactInformation->name, 'id' => $this->customer->id];
        $resource['primary_rate_card'] = ['url' => route('rate_cards.edit', ['rate_card' => $this->primaryRateCard()]), 'name' => $this->primaryRateCard()->name];
        $resource['secondary_rate_card'] = ['url' => route('rate_cards.edit', ['rate_card' => $this->secondaryRateCard()]), 'name' => $this->secondaryRateCard()->name];
        $resource['period_start'] = localized_date($this->period_start);
        $resource['period_end'] =  localized_date($this->period_end);
        $resource['amount'] = $this->amount;
        $resource['invoice_number'] = $this->invoice_number;

        return $resource;
    }
}
