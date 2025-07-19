<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\FormRequest;

class ChangeLocationLotRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'product_id' => [
                'nullable',
                'exists:products,id'
            ],
            'location_id' => [
                'nullable',
                'exists:locations,id'
            ],
            'lot_item_id' => [
                'nullable',
                'exists:lot_items,id'
            ],
            'lot_id' => [
                'exists:lots,id'
            ]
        ];
    }
}
