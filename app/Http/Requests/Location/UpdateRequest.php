<?php

namespace App\Http\Requests\Location;

use App\Http\Requests\FormRequest;

class UpdateRequest extends FormRequest
{
    public static function validationRules(): array
    {
        $rules = StoreRequest::validationRules();

        unset($rules['warehouse_id']);

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required',
        ];
    }
}
