<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingCustomerTableResource extends JsonResource
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

        $resource['id'] = $this->id;
        $resource['name'] = $this->contactInformation->name;
        $resource['company_name'] = $this->contactInformation->company_name;
        $resource['address'] = $this->contactInformation->address;
        $resource['address2'] = $this->contactInformation->address2;
        $resource['zip'] = $this->contactInformation->zip;
        $resource['city'] = $this->contactInformation->city;
        $resource['email'] = $this->contactInformation->email;
        $resource['phone'] = $this->contactInformation->phone;
        $resource['link_edit'] =  route('customer.edit', ['customer' => $this]);
        $resource['link_delete'] = [
            'token' => csrf_token(),
            'url' => route('customer.destroy', ['id' => $this->id, 'customer' => $this])
        ];

        $resource['primary_rate_card'] = [
            'url' => $this->primaryRateCard() ? route('rate_cards.edit', ['rate_card' => $this->primaryRateCard() ]) : '',
            'name' => $this->primaryRateCard()->name ?? ''
        ];

        $resource['secondary_rate_card'] = [
            'url' => $this->secondaryRateCard() ? route('rate_cards.edit', ['rate_card' => $this->secondaryRateCard() ]) : '',
            'name' => $this->secondaryRateCard()->name ?? ''
        ];

        $invoice = $this->lastBill();

        $resource['last_billed'] = [
            'url' => route('billings.customer_invoice_line_items', ['customer' => $this->id, 'invoice' => $invoice->id ?? '']),
            'amount' => $invoice->amount ?? '',
            'period_start' => $invoice ? localized_date($invoice->period_start) : '',
            'period_end' => $invoice ? localized_date($invoice->period_end) : '',
            'calculated_at' => $invoice ? user_date_time($invoice->calculated_at) : ''
        ];

        return $resource;
    }
}
