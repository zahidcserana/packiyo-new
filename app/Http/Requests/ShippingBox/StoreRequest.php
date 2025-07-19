<?php

namespace App\Http\Requests\ShippingBox;

use App\Http\Requests\FormRequest;

class StoreRequest extends FormRequest
{
    public static function validationRules()
    {
        return [
            'type' => [
                'sometimes'
            ],
            'name' => [
                'required',
                'min:3'
            ],
            'customer_id' => [
                'required',
                'exists:customers,id'
            ],
            'weight' => [
                'required',
                'numeric'
            ],
            'width' => [
                'required',
                'numeric'
            ],
            'height' => [
                'required',
                'numeric'
            ],
            'length' => [
                'required',
                'numeric'
            ],
            'height_locked' => [
                'sometimes',
                'integer'
            ],
            'length_locked' => [
                'sometimes',
                'integer'
            ],
            'width_locked' => [
                'sometimes',
                'integer'
            ],
            'weight_locked' => [
                'sometimes',
                'integer'
            ],
            'cost' => [
                'sometimes',
                'numeric',
                'nullable'
            ]
        ];
    }
}
