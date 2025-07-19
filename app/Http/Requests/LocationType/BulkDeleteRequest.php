<?php

namespace App\Http\Requests\LocationType;

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
                'exists:location_types,id,deleted_at,NULL'
            ]
        ];
    }
}
