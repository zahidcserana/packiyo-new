<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BillingTableResource extends JsonResource
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
        $resource['name'] = $this->contactInformation->name;
        $resource['rate_card'] = ['url' => route('rate_cards.edit', ['rate_card' => $this->rateCard ?? null]), 'name' => $this->rateCard->name ?? ''];
        $resource['company_name'] = $this->contactInformation->company_name;
        $resource['invoices'] = ['count' => $this->invoices_count, 'latest' => $this->latest_invoice];
        $resource['link_show'] =  route('billings.show', ['customer' => $this]);

        return $resource;
    }
}
