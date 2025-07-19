<?php

namespace App\Http\Requests\BillingRate;

use App\Http\Requests\FormRequest;

class ShipmentsByPickingRateUpdateRequestV2 extends FormRequest
{
    public static function validationRules()
    {
        return ShipmentsByPickingRateStoreRequestV2::validationRules();
    }
}
