<?php

namespace App\Http\Requests\BillingRate;

use App\Http\Requests\FormRequest;

class AdHocUpdateRequest extends FormRequest
{
    public static function validationRules()
    {
        return AdHocStoreRequest::validationRules();
    }
}
