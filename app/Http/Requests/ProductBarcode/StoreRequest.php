<?php

namespace App\Http\Requests\ProductBarcode;

use App\Features\PreventDuplicateBarcodes;
use App\Http\Requests\FormRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Rules\IsDuplicateBarcode;
use App\Rules\UniqueProductBarcode;
use Laravel\Pennant\Feature;

class StoreRequest extends FormRequest
{
    public static function validationRules(): array
    {
        $productId = static::getInputField('product_id');

        $rules = [
            'product_id' => [
                'required',
                'exists:products,id'
            ],
            'barcode' => [
                'required',
                new UniqueProductBarcode(ProductBarcode::class, $productId)
            ],
            'quantity' => [
                'required',
                'min:0'
            ],
            'description' => [
                'sometimes'
            ]
        ];

        $product = Product::find($productId);
        $customer = Customer::find($product->customer_id);

        if ((Feature::for($customer)->active(PreventDuplicateBarcodes::class) || Feature::for('instance')->active(PreventDuplicateBarcodes::class)) && $productId) {
            $rules['barcode'][] = new IsDuplicateBarcode($customer->id, $productId);
        }

        return $rules;
    }
}
