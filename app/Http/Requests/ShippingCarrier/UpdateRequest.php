<?php

namespace App\Http\Requests\ShippingCarrier;

use App\Http\Requests\FormRequest;

class UpdateRequest extends FormRequest
{
    public static function validationRules()
    {
        $rules = [
            'id' => [
                'required',
                'exists:shipping_carriers,id,deleted_at,NULL'
            ],
            'configurations' => [
                'required',
                'array'
            ]
        ];

        return array_merge_recursive($rules, ConfigurationItemRequest::prefixedValidationRules('configurations.*.'));
    }
}
