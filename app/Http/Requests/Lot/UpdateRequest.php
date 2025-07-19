<?php

namespace App\Http\Requests\Lot;

use App\Http\Requests\FormRequest;

class UpdateRequest extends FormRequest
{
    public static function validationRules()
    {
        $rules = StoreRequest::validationRules();

        return $rules;
    }
}
