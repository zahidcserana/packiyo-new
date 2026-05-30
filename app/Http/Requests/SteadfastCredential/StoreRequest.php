<?php

namespace App\Http\Requests\SteadfastCredential;

use App\Http\Requests\FormRequest;

class StoreRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'customer_id' => [
                'sometimes',
                'exists:customers,id',
            ],
            'api_base_url' => [
                'sometimes',
                'url',
            ],
            'api_key' => [
                'required',
            ],
            'secret_key' => [
                'required',
            ],
        ];
    }
}
