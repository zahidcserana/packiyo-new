<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\FormRequest;
use App\Models\Lot;
use App\Models\Product;
use App\Rules\LocationFulfillingLotConditions;

class AddToLocationRequest extends FormRequest
{
    public static Product $product;
    public static ?Lot $lot;

    public static function validationRules(): array
    {
        if ($productId = self::$formRequest->input('product_id')) {
            self::$product = Product::withTrashed()->find($productId);
        } else {
            self::$product = request('product');
        }

        self::$lot = Lot::find(self::$formRequest->input('lot_id'));

        $rules = [
            // only required when fabricating requests
            'product_id' => [
                'nullable',
                'exists:products,id',
            ],
            'location_id' => [
                'required',
                'exists:locations,id,deleted_at,NULL',
                new LocationFulfillingLotConditions(self::$product, self::$lot),
            ],
            'quantity' => [
                'required',
                'numeric'
            ]
        ];

        if (self::$product->lot_tracking) {
            $rules['lot_id'] = [
                'required',
                'exists:lots,id'
            ];
        }

        return $rules;
    }
}
