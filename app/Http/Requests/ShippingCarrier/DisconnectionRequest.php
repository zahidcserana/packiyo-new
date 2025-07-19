<?php

namespace App\Http\Requests\ShippingCarrier;

use App\Http\Requests\FormRequest;

class DisconnectionRequest extends FormRequest
{
    public static function validationRules()
    {
        $rules = [
            'disconnection_text' => [
                'required'
            ]
        ];

        return $rules;
    }
}
