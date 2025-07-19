<?php

namespace App\Http\Requests\ExternalCarrierCredential;

use App\Http\Requests\FormRequest;

class StoreRequest extends FormRequest
{
    public static function validationRules(): array
    {
        $customerId = static::getInputField('customer_id');

        $rules = [
            'customer_id' => [
                'required',
                'exists:customers,id,deleted_at,NULL'
            ],
            'reference' => [
                'nullable'
            ],
            'get_carriers_url' => [
                'nullable'
            ],
            'create_shipment_label_url' => [
                'nullable'
            ],
            'create_return_label_url' => [
                'nullable'
            ],
            'void_label_url' => [
                'nullable'
            ],
        ];

        return $rules;
    }
}
