<?php

namespace App\Http\Requests\TribirdOrderChannel;

use App\Http\Requests\FormRequest;

class UpdateUserNameRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'name' => [
                'required'
            ]
        ];
    }
}
