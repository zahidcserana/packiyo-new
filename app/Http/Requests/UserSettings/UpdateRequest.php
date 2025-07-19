<?php

namespace App\Http\Requests\UserSettings;

use App\Http\Requests\FormRequest;

class UpdateRequest extends FormRequest
{
    public static function validationRules()
    {
        $rules = [];

        $rules['key'] = ['required'];
        $rules['value'] = ['sometimes'];

        return $rules;
    }
}
