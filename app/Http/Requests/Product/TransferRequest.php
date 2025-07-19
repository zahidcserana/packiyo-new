<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\FormRequest;
use App\Models\Location;
use App\Models\LocationProduct;
use App\Models\Lot;
use App\Models\Product;
use App\Rules\LocationFulfillingLotConditions;

class TransferRequest extends FormRequest
{
    public static Product $product;

    public static function validationRules(): array
    {
        if ($productId = self::$formRequest->input('product_id')) {
            self::$product = Product::find($productId);
        } else {
            self::$product = request('product');
        }

        $fromLot = Lot::find(self::$formRequest->get('lot_id'));

        $fromLocation = Location::find(self::$formRequest->get('from_location_id'));

        $fromLocationProduct = LocationProduct::where('product_id', self::$product->id)
            ->where('location_id', $fromLocation->id)
            ->firstOrFail();

        return [
            // only required when fabricating requests
            'product_id' => [
                'nullable',
                'exists:products,id',
            ],
            'lot_id' => [
                'nullable',
                'exists:lots,id',
            ],
            'from_location_id' => [
                'required',
                'numeric',
            ],
            'to_location_id' => [
                'required',
                'numeric',
                new LocationFulfillingLotConditions(
                    self::$product,
                    $fromLot,
                    $fromLocation,
                ),
                'different:from_location_id',
            ],
            'quantity' => [
                'required',
                'numeric',
                'gt:0',
                'lte:' . $fromLocationProduct->quantity_on_hand,
            ],
        ];
    }
}
