<?php

namespace App\Http\Requests\Tote;

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
                'exists:totes,id,deleted_at,NULL'
            ]
        ];
    }
}
