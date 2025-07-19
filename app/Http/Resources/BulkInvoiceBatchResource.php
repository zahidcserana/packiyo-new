<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BulkInvoiceBatchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'other_customers' => $this->other_customers,
            'number' => $this->id,
            'amount' => $this->amount,
            'name' => $this->name,
            'updated_at' =>  user_date_time($this->updated_at, true),
            'period' => $this->period,
            'link_delete' => [
                'token' => csrf_token(),
                'url' => route('bulk_invoice_batches.destroy', ['bulk_invoice_batch' => $this->id]),
            ],
            'link_finalize' => [
                'token' => csrf_token(),
                'url' => route('bulk_invoice_batches.finalize', ['bulk_invoice_batch' => $this->id]),
            ],
            'link_recalculate' => [
                'token' => csrf_token(),
                'url' => route('bulk_invoice_batches.recalculate', ['bulk_invoice_batch' => $this->id]),
            ],
            'link_edit' => route(
                    'bulk_invoice_batches.edit',
                    ['bulk_invoice_batch' => $this->id]
                ),
        ];
    }
}
