<?php

namespace App\Http\Requests\RateCard;

use App\Http\Requests\FormRequest;

class StoreRequest extends FormRequest
{
    public static function validationRules()
    {
        return [
            'name' => [
                'required'
            ],
            'monthly_cost' => [
                'sometimes'
            ],
            'per_user_cost' => [
                'sometimes'
            ],
            'per_purchase_order_received_cost' => [
                'sometimes'
            ],
            'per_product_cost' => [
                'sometimes'
            ],
            'per_shipment_cost' => [
                'sometimes'
            ],
            'per_return_cost' => [
                'sometimes'
            ],
            '3pl_id' => [
                'required'
            ]
        ];
    }
}
