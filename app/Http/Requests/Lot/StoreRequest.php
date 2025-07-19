<?php

namespace App\Http\Requests\Lot;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public static function validationRules()
    {
        return [
            'name' => [
                'required',
                'min:3',
                Rule::unique('lots')
                    ->where(function ($query) {
                        return $query
                            ->where('name', self::$formRequest->get('name'))
                            ->where('supplier_id', self::$formRequest->get('supplier_id'))
                            ->where('product_id', self::$formRequest->get('product_id'));
                    })->ignore(self::$formRequest->lot ?? null),
            ],
            'expiration_date' => [
                'nullable',
                'date'
            ],
            'product_id' => [
                'required',
                'exists:products,id'
            ],
            'supplier_id' => [
                'required',
                'exists:suppliers,id'
            ],
            'item_price' => [
                'nullable',
                'numeric'
            ],
        ];
    }
}
