<?php

namespace App\Http\Requests;

use App\Rules\BulkPrintable;

class BulkPrintRequest extends FormRequest
{
    public static function validationRules(): array
    {
        $column = static::getInputField('column');

        return [
            'model_name' => [
                new BulkPrintable($column),
            ],
            'model_ids' => [
                'array',
            ],
            'model_ids.*' => [
                'integer',
            ],
            'column' => [
                'required',
                'string'
            ],
            'relation' => [
                'sometimes'
            ]
        ];
    }
}
