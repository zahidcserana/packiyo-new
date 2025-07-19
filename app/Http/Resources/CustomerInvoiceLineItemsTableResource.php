<?php

namespace App\Http\Resources;

use App\Models\BillingRate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerInvoiceLineItemsTableResource extends JsonResource
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
        $resource['invoice_id'] = $this->invoice_id;
        $resource['billing_rate_type'] = BillingRate::BILLING_RATE_TYPES[$this->billingRate->type]['title'];
        $resource['billing_rate_code'] = $this->billingRate->code;
        $resource['created_at'] = user_date_time($this->created_at, true);
        $resource['description'] = $this->description;
        $resource['total_charge'] = $this->total_charge;
        $resource['customer_name'] = $this->invoice->customer->contactInformation->name;
        $resource['link_delete'] = [
            'token' => csrf_token(),
            'url' => route(
                'invoices.delete_ad_hoc',
                ['invoice' => $this->invoice_id, 'invoice_line_item' => $this->id]
            )
        ];

        return $resource;
    }
}
