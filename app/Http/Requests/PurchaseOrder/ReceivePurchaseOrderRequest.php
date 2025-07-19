<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Http\Requests\FormRequest;

class ReceivePurchaseOrderRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'location_id' => [
                'array',
                'required',
                'min:1',
            ],
            'location_id.*' => [
                'exists:locations,id,deleted_at,NULL'
            ],
            'lot_id' => [
                'array',
                'sometimes'
            ],
            'lot_id.*' => [
                'nullable',
                'integer',
            ],
            'lot_tracking' => [
                'array',
                'required'
            ],
            'lot_tracking.*' => [
                'required',
                'integer'
            ],
            'quantity_received' => [
                'array',
                'required'
            ],
            'quantity_received.*' => [
                'required',
                'integer'
            ],
            'product_id' => [
                'array',
                'required'
            ],
            'product_id.*' => [
                'required',
                'numeric',
                'exists:products,id'
            ],
        ];
    }
}
