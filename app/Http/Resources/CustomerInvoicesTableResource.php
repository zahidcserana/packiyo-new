<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class CustomerInvoicesTableResource extends JsonResource
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
        $resource['primary_rate_card'] = $this->primaryRateCard()->name ?? 'Not set';
        $resource['secondary_rate_card'] = $this->secondaryRateCard()->name ?? 'Not set';
        $resource['period_start'] = localized_date($this->period_start);
        $resource['period_end'] =  localized_date($this->period_end);
        $resource['amount'] = $this->amount;
        $resource['invoice_number'] = $this->invoice_number ?? 'Not set';
        $resource['calculated_at'] = user_date_time($this->calculated_at, true) ?? __('calculating');
        $resource['is_finalized'] = $this->is_finalized;
        $resource['status'] = strtoupper($this->getInvoiceStatus()->value) ;
        $resource['is_readonly_user'] = !Auth::user()->isAdmin();

        return $resource;
    }
}
