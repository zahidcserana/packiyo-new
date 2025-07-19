<?php

namespace App\Http\Requests\ShippingCarrier;

use App\Http\Requests\FormRequest;

class ConfigurationItemRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'field' => [
                'required'
            ],
            'value' => [
                'nullable'
            ]
        ];
    }
}
