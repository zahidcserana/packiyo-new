<?php

namespace App\Http\Requests\Easypost\CarrierAccount;

use App\Http\Requests\FormRequest;

class CreateAccountRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'customer_id' => [
                'required',
                'exists:customers,id,deleted_at,NULL'
            ],
            'easypost_credential_id' => [
                'required',
                'exists:easypost_credentials,id,deleted_at,NULL'
            ],
            'type' => [
                'required'
            ],
            'description' => [
                'string',
                'nullable'
            ],
            'credentials.*' => [
                'sometimes'
            ],
            'test_credentials.*' => [
                'sometimes'
            ]
        ];
    }
}
