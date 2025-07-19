<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\FormRequest;

class FilterRequest extends FormRequest
{
    public static function validationRules()
    {
        $rules = [
            'from_date_created' => [
                'sometimes'
            ],
            'to_date_created' => [
                'sometimes'
            ],
            'from_date_updated' => [
                'sometimes'
            ],
            'to_date_updated' => [
                'sometimes'
            ]
        ];

        return $rules;
    }
}
