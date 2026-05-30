<?php

namespace App\Http\Requests\SteadfastCredential;

use App\Http\Requests\FormRequest;

class DestroyRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:steadfast_credentials,id,deleted_at,NULL',
            ],
        ];
    }
}
