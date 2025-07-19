<?php

namespace App\Http\Requests\BillingRate;

use App\Http\Requests\FormRequest;

class PackagingRateUpdateRequest extends FormRequest
{
    public static function validationRules()
    {
        return PackagingRateStoreRequest::validationRules();
    }
}
