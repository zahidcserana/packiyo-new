<?php

namespace App\Http\Requests\PickingBatch;

use App\Http\Requests\FormRequest;

class MultiOrderRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'tag_id' => [
                'sometimes',
                'integer',
                'nullable'
            ],
            'tag_name' => [
                'sometimes',
                'string',
                'nullable'
            ],
            'quantity' => [
                'required',
                'integer'
            ],
            'customer_id' => [
                'required',
                'exists:customers,id,deleted_at,NULL'
            ]
        ];
    }
}
