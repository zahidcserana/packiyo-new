<?php

namespace App\Http\Requests\RateCard;

use App\Http\Requests\FormRequest;

class UpdateRequest extends FormRequest
{
    public static function validationRules()
    {
        $rules = StoreRequest::validationRules();
        $rules['id'] = ['required', 'exists:rate_cards,id,deleted_at,NULL'];
        $rules['3pl_id'] = ['sometimes', 'exists:customers,id,deleted_at,NULL'];

        return $rules;
    }
}
