<?php

namespace App\Http\Requests\BillingRate;

use App\Http\Requests\FormRequest;

class ShipmentsByShippingLabelStoreRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return array_merge(
            [
                'is_enabled' => [
                    'sometimes'
                ],
                'name' => [
                    'required'
                ],
                'code' => [
                    'sometimes'
                ],
                'settings' => [
                    'required'
                ],
            ]
        );
    }
}
