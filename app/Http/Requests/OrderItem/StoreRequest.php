<?php

namespace App\Http\Requests\OrderItem;

use App\Http\Requests\FormRequest;

class StoreRequest extends FormRequest
{
    public static $customerId;

    public static function validationRules()
    {
        return [
            'external_id' => [
                'sometimes',
                'distinct'
            ],
            'product_id' => [
                'required_without_all:sku,barcode'
            ],
            'sku' => [
                'required_without_all:product_id,barcode'
            ],
            'barcode' => [
                'required_without_all:product_id,sku'
            ],
            'quantity' => [
                'required',
                'numeric',
                'min:0',
            ],
            'quantity_pending' => [
                'nullable',
                'numeric'
            ],
            'quantity_shipped' => [
                'nullable',
                'numeric'
            ],
            'price' => [
                'nullable',
                'numeric'
            ],
            'name' => [
                'nullable'
            ]
        ];
    }
}
