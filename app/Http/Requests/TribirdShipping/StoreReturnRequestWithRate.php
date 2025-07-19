<?php

namespace App\Http\Requests\TribirdShipping;
use App\Http\Requests\Order\StoreReturnRequest;
use App\Http\Requests\FormRequest;

class StoreReturnRequestWithRate extends FormRequest
{
    public static function validationRules()
    {
        $rules = StoreReturnRequest::validationRules();
        
        $rules['rate'] = ['sometimes'];
        $rules['rate_id'] = ['sometimes'];

        return $rules;
    }
}
