<?php

namespace App\Http\Requests\BillingRate;

use App\Http\Requests\FormRequest;

class PurchaseOrderStoreRequest extends FormRequest
{
    public static function validationRules()
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
