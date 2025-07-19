<?php

namespace App\Http\Requests\Tote;

use App\Http\Requests\FormRequest;
use App\Models\OrderItem;
use App\Rules\PickOrderItemRule;

class PickOrderItemsRequest extends FormRequest
{
    public static function validationRules(): array
    {
        $tote = request()->route('tote');

        return [
            'location_id' => [
                'required',
                'exists:locations,id,deleted_at,NULL'
            ],
            'order_item_id' => [
                'required',
                'exists:order_items,id,deleted_at,NULL',
                new PickOrderItemRule(OrderItem::class, $tote)
            ],
            'quantity' => [
                'required',
                'integer'
            ]
        ];
    }
}
