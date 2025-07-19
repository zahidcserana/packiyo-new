<?php

namespace App\Http\Requests\TribirdOrderChannel;

use App\Http\Requests\FormRequest;

class ConfigurationUpdateRequest extends FormRequest
{
    public static function validationRules()
    {
        $rules = [
            'configurations' => [
                'required',
                'array'
            ]
        ];

        return array_merge_recursive($rules, ConfigurationItemRequest::prefixedValidationRules('configurations.*.'));
    }
}
