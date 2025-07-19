<?php

namespace App\Http\Requests\EasypostCredential;

use App\Http\Requests\FormRequest;

class StoreRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'customer_id' => [
                'sometimes',
                'exists:customers,id'
            ],
            'api_key' => [
                'required'
            ],
            'test_api_key' => [
                'nullable'
            ],
            'use_native_tracking_urls' => [
                'sometimes'
            ],
            'commercial_invoice_signature' => [
                'sometimes'
            ],
            'commercial_invoice_letterhead' => [
                'sometimes'
            ],
            'endorsement' => [
                'sometimes'
            ],
        ];
    }
}
