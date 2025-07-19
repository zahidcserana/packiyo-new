<?php

namespace App\Http\Requests\BillingRate;

use App\Http\Requests\FormRequest;

class PurchaseOrderUpdateRequest extends FormRequest
{
    public static function validationRules()
    {
        return PurchaseOrderStoreRequest::validationRules();
    }
}
