<?php

namespace App\Http\Requests\RateCard;

use App\Http\Requests\FormRequest;

class DestroyRequest extends FormRequest
{
    public static function validationRules()
    {
        $rules = [
            'id' => [
                'required',
                'exists:rate_cards,id,deleted_at,NULL'
            ]
        ];

        return $rules;
    }
}
