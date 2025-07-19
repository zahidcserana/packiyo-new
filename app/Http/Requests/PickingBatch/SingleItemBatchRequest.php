<?php

namespace App\Http\Requests\PickingBatch;

use App\Http\Requests\FormRequest;

class SingleItemBatchRequest extends FormRequest
{
    public static function validationRules()
    {
        $rules = [
            'tag_id' => [
                'sometimes'
            ],
            'tag_name' => [
                'sometimes'
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

        return $rules;
    }
}
