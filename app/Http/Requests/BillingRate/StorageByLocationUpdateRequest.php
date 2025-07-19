<?php

namespace App\Http\Requests\BillingRate;

use App\Http\Requests\FormRequest;

class StorageByLocationUpdateRequest extends FormRequest
{
    public static function validationRules()
    {
        return StorageByLocationStoreRequest::validationRules();
    }
}
