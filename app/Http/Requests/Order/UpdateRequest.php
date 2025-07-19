<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\FormRequest;
use App\Models\Order;
use App\Models\ShippingBox;
use App\Rules\BelongsToCustomer;

class UpdateRequest extends FormRequest
{
    public static function validationRules(): array
    {
        $order = Order::find(static::getInputField('id'));
        $customerId = $order ? $order->customer_id : static::getInputField('customer_id');
        $rules = StoreRequest::validationRules();

        // TODO: also add BelongsTo
        $rules['id'] = [
            'nullable'
        ];

        foreach ($rules['shipping_box_id'] as $key => $rule) {
            if (!is_string($rule) && get_class($rule) === BelongsToCustomer::class) {
                unset($rules['shipping_box_id'][$key]);
                $rules['shipping_box_id'][$key] = new BelongsToCustomer(ShippingBox::class, $customerId);
            }
        }

        unset($rules['external_id'], $rules['number']);

        foreach ($rules['order_items.*.quantity'] as $key => $rule) {
            if (str_contains($rule, 'min:0')) {
                unset($rules['order_items.*.quantity'][$key]);
            }
            if (str_contains($rule, 'not_in:0')) {
                unset($rules['order_items.*.quantity'][$key]);
            }
        }

        $rules['order_items.*.order_item_id'] = ['sometimes', 'exists:order_items,id'];

        foreach ($rules['order_status_id'] as $key => $rule) {
            if (is_string($rule) && str_contains($rule, 'required')) {
                $rules['order_status_id'][$key] = 'sometimes';
            }
        }

        foreach ($rules['order_items'] as $key => $rule) {
            if (is_string($rule) && str_contains($rule, 'required')) {
                $rules['order_items'][$key] = 'sometimes';
            }
        }

        $rules['order_items.*.product_id'] = ['sometimes'];
        $rules['order_items.*.sku'] = ['sometimes'];

        return $rules;
    }
}
