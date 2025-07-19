<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingCustomerInvoiceResource extends JsonResource
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

        $resource['date'] = $this->date;
        $resource['rate_card'] = ['url' => route('rate_cards.edit', ['rate_card' => $this->rateCard]), 'name' => $this->rateCard->name];
        $resource['direct_url'] = ['url' => route('invoice.direct_url', ['direct_url' => $this->direct_url]), 'name' => $this->direct_url];
        $resource['link_edit'] = route('invoices.edit', ['invoice' => $this->id]);

        return $resource;
    }
}
