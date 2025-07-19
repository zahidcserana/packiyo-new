<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\FormRequest;

class FilterRequest extends FormRequest
{
    public static function validationRules()
    {
        $rules = [
            'from_date_created' => [
                ''
            ],
            'to_date_created' => [
                'sometimes'
            ],
            'from_date_updated' => [
                'sometimes'
            ],
            'to_date_updated' => [
                'sometimes'
            ],
            'location_id' => [
                'exists:locations,id,deleted_at,NULL'
            ],
            'customer_id' => [
                'exists:customers,id,deleted_at,NULL'
            ],
        ];

        return $rules;
    }
}
