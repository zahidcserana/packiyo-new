<?php

namespace App\Http\Requests\ReturnItem;

use App\Http\Requests\FormRequest;

class FilterRequest extends FormRequest
{
    public static function validationRules()
    {
        $rules = [
            'from_date_created' => [
                'required',
            ],
            'to_date_created' => [
                'sometimes',                
            ],
            'product_id' => [
                'required',
                'exists:products,id,deleted_at,NULL'
            ],
        ];

        return $rules;
    }
}