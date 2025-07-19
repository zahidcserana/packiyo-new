<?php

namespace App\Http\Requests\ShippingCarrier;

use App\Http\Requests\FormRequest;

class StoreRequest extends FormRequest
{
    public static function validationRules()
    {
        $rules = [
            'customer_id' => [
                'sometimes',
                'exists:customers,id'
            ],
            'carrier_type' => [
                'sometimes'
            ],
            'configurations' => [
                'required',
                'array'
            ]
        ];

        return array_merge_recursive($rules, ConfigurationItemRequest::prefixedValidationRules('configurations.*.'));
    }
}
