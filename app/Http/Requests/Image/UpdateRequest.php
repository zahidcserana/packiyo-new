<?php

namespace App\Http\Requests\Image;

use App\Http\Requests\FormRequest;

class UpdateRequest extends FormRequest
{
    public static function validationRules()
    {
        $rules = [
            'id' => [
                'required',
                'exists:images,id,deleted_at,NULL'
            ]
        ];

        return $rules;
    }
}
