<?php

namespace App\Http\Requests\PickingBatch;

use App\Http\Requests\FormRequest;

class SingleOrderRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'order_id' => [
                'required',
                'exists:orders,id,deleted_at,NULL',
            ],
            'customer_id' => [
                'required',
                'exists:customers,id,deleted_at,NULL',
            ]
        ];
    }
}
