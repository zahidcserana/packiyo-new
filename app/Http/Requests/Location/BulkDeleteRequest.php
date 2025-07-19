<?php

namespace App\Http\Requests\Location;

use App\Http\Requests\FormRequest;

class BulkDeleteRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'ids' => [
                'required'
            ],
            'ids.*' => [
                'exists:locations,id,deleted_at,NULL'
            ]
        ];
    }
}
