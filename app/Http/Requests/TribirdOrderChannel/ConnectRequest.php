<?php

namespace App\Http\Requests\TribirdOrderChannel;

use App\Http\Requests\FormRequest;
use App\Rules\UniqueForCustomer;
use App\Models\OrderChannel;

class ConnectRequest extends FormRequest
{
    public static function validationRules()
    {
        $customerId = static::getInputField('customer_id');

        $rules = [
            'customer_id' => [
                'required',
                'exists:customers,id'
            ],
            'order_channel_type' => [
                'required'
            ],
            'oauth_connection' => [
                'required'
            ],
            'name' => [
                'required',
                new UniqueForCustomer(OrderChannel::class, $customerId)
            ],
            'configurations' => [
                'required',
                'array'
            ],
            'migrate_to_order_channel_id' => [
                'nullable'
            ]
        ];

        return array_merge_recursive($rules, ConfigurationItemRequest::prefixedValidationRules('configurations.*.'));
    }
}
