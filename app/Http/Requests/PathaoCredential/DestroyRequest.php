<?php

namespace App\Http\Requests\PathaoCredential;

use App\Http\Requests\FormRequest;

class DestroyRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:pathao_credentials,id,deleted_at,NULL'
            ]
        ];
    }
}
