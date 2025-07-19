<?php

namespace App\Http\Requests\ExternalCarrierCredential;

use App\Http\Requests\FormRequest;

class DestroyRequest extends FormRequest
{
    public static function validationRules()
    {
        $rules = [
            'id' => [
                'required',
                'exists:external_carrier_credentials,id,deleted_at,NULL'
            ]
        ];

        return $rules;
    }
}
