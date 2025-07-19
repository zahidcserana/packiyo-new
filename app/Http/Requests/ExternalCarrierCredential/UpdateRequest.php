<?php

namespace App\Http\Requests\ExternalCarrierCredential;

use App\Http\Requests\FormRequest;

class UpdateRequest extends FormRequest
{
    public static function validationRules(): array
    {
        $rules = StoreRequest::validationRules();

        $rules['id'] = [
            'nullable'
        ];

        return $rules;
    }
}
