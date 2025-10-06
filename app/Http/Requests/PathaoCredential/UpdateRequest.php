<?php

namespace App\Http\Requests\PathaoCredential;

class UpdateRequest extends StoreRequest
{
    public static function validationRules()
    {
        $rules = parent::validationRules();

        $rules['id'] = [
            'sometimes',
        	'exists:pathao_credentials,id,deleted_at,NULL'
        ];

        return $rules;
    }
}
