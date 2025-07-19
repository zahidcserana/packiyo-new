<?php

namespace App\Http\Requests\Location;

use App\Http\Requests\FormRequest;
use App\Rules\Location\Unprotected;

class DestroyRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'id' => [
                'required', 'exists:locations,id,deleted_at,NULL', new Unprotected()
            ]
        ];
    }
}
