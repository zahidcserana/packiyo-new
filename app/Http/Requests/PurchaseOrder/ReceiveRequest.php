<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Http\Requests\FormRequest;
use App\Models\Lot;
use App\Models\Product;
use App\Models\PurchaseOrderItem;
use App\Rules\LocationFulfillingLotConditions;

class ReceiveRequest extends FormRequest
{
    public static function validationRules(): array
    {
        $purchaseOrderItemId = static::getInputField('purchase_order_item_id');

        $purchaseOrderItem = PurchaseOrderItem::find($purchaseOrderItemId);
        $product = Product::find($purchaseOrderItem->product_id);
        $lot = Lot::find(self::$formRequest->input('lot_id'));

        $rules = [
            'purchase_order_item_id' => [
                'required',
                'validate_purchase_order_item',
            ],
            'location_id' => [
                'required',
                'exists:locations,id,deleted_at,NULL',
                new LocationFulfillingLotConditions($product, $lot),
            ],
            'quantity_received' => [
                'required',
                'numeric',
            ]
        ];

        if ($product->lot_tracking) {
            $rules['lot_id'] = [
                'required',
                'exists:lots,id'
            ];
        }

        return $rules;
    }
}
