<?php

namespace App\Http\Requests\BillingRate;

use App\Http\Requests\FormRequest;

class ShipmentsByShippingLabelUpdateRequest extends FormRequest
{
    public static function validationRules()
    {
        return ShipmentsByShippingLabelStoreRequest::validationRules();
    }
}
