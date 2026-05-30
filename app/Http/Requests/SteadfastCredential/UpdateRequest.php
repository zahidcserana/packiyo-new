<?php

namespace App\Http\Requests\SteadfastCredential;

class UpdateRequest extends StoreRequest
{
    public static function validationRules(): array
    {
        $rules = parent::validationRules();

        $rules['id'] = [
            'sometimes',
            'exists:steadfast_credentials,id,deleted_at,NULL',
        ];

        return $rules;
    }
}
