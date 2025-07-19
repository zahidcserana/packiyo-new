<?php

namespace App\Http\Requests\Image;

use App\Http\Requests\FormRequest;

class StoreRequest extends FormRequest
{
    public static function validationRules()
    {
        $rules = [
            'file' => [
                'required',
                'image',
                'max:8192',
            ]
        ];

        return $rules;
    }
}
