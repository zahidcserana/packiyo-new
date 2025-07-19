<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Http\Requests\FormRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Rules\UniqueForCustomer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class UpdateRequest extends FormRequest
{
    private static ?PurchaseOrder $purchaseOrder;

    public static function validationRules()
    {
        $rules = StoreRequest::validationRules();

        static::$purchaseOrder = request()->route('purchase_order');

        if (!static::$purchaseOrder) {
            static::$purchaseOrder = PurchaseOrder::find(self::$recordId);
        }

        static::$purchaseOrder->load([
            'purchaseOrderItems' => function (HasMany $query) {
                $query->select('id', 'purchase_order_id', 'quantity');
            }
        ]);

        unset($rules['customer_id']);
        unset($rules['external_id']);

        foreach (Arr::get($rules, 'warehouse_id', []) as $key => $rule) {
            if ($rule == 'required') {
                $rules['warehouse_id'][$key] = 'nullable';
            }
        }

        unset($rules['number']);

        $rules['number'] = [
            new UniqueForCustomer(PurchaseOrder::class, static::$purchaseOrder->customer_id, static::$purchaseOrder->id)
        ];

        $rules['purchase_order_items'] = [
            function (string $attribute, array $value) {
                foreach ($value as $index => $purchaseOrderItem) {
                    if (Arr::get($purchaseOrderItem, 'has_quantity_changed')) {
                        Validator::validate([
                            'purchase_order_items' => [
                                $index => $purchaseOrderItem
                            ]
                        ], [
                            'purchase_order_items.*.quantity' => [
                                'gte:purchase_order_items.*.quantity_received'
                            ]
                        ]);
                    }
                }
            },
        ];

        foreach ($rules['purchase_order_items.*.quantity'] as $key => $rule) {
            if (is_callable($rule)) {
                continue;
            }

            if (str_contains($rule, 'min:0')) {
                unset($rules['purchase_order_items.*.quantity'][$key]);
            }

            if (str_contains($rule, 'not_in:0')) {
                unset($rules['purchase_order_items.*.quantity'][$key]);
            }
        }

        $rules['purchase_order_items.*.purchase_order_item_id'] = ['sometimes', 'exists:purchase_order_items,id'];

        return $rules;
    }

    protected function prepareForValidation()
    {
        $input = $this->all();

        if (isset($input['purchase_order_items'])) {
            foreach ($input['purchase_order_items'] as &$purchaseOrderItemInput) {
                $purchaseOrderItemId = Arr::get($purchaseOrderItemInput, 'purchase_order_item_id');

                if (!$purchaseOrderItemId) {
                    continue;
                }

                $purchaseOrderItem = PurchaseOrderItem::find($purchaseOrderItemId);

                if (!isset($purchaseOrderItemInput['quantity_received'])) {
                    $purchaseOrderItemInput['quantity_received'] = 0;

                    $purchaseOrderItemInput['quantity_received'] = $purchaseOrderItem->quantity_received;
                }

                $purchaseOrderItemInput['has_quantity_changed'] = $purchaseOrderItem->quantity != $purchaseOrderItemInput['quantity'];
            }
        }

        $this->replace($input);
    }

    public function messages()
    {
        return [
            'purchase_order_items.*.quantity.gte' => __(':attribute on :number cannot be less than :value - currently set to :input', [
                'number' => self::$purchaseOrder->number
            ])
        ];
    }
}
