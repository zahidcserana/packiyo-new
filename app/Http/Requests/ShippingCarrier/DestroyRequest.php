<?php

namespace App\Http\Requests\ShippingCarrier;

use App\Http\Requests\FormRequest;

class DestroyRequest extends FormRequest
{
    public static function validationRules()
    {
        $rules = [
            'id' => [
                'required',
                'exists:shipping_carriers,id,deleted_at,NULL'
            ]
        ];

        return $rules;
    }
}
