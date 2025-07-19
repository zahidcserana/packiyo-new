<?php

namespace App\Http\Requests\Shipment;

use App\Http\Requests\FormRequest;
use App\Models\OrderStatus;
use App\Rules\BelongsToCustomer;
use App\Rules\ExistsOrStaticValue;
use App\Rules\OrderItemsCanBeReshipped;

class ReShipRequest extends FormRequest
{
    public static function validationRules()
    {
        $customerId = static::getInputField('customer_id');

        $rules = array_merge(
            [
                'order_items' => [
                    'required',
                    'array',
                    new OrderItemsCanBeReshipped,
                ],

                'order_items.*.order_item_id' => [
                    'sometimes',
                    'integer',
                    'exists:order_items,id,deleted_at,NULL'
                ],

                'order_items.*.quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'order_items.*.add_inventory' => [
                    'sometimes',
                    'integer'
                ],

                'operator_hold' => [
                    'sometimes',
                    'integer'
                ],

                'reship_order_status_id' => [
                    'nullable',
                    new ExistsOrStaticValue('order_statuses', 'id', 'pending'),
                    new BelongsToCustomer(OrderStatus::class, $customerId, 'pending')
                ]
            ]
        );

        return $rules;
    }

    public function messages(): array
    {
        return [
            'order_items.required' => 'At least one order item needs to be selected'
        ];
    }
}
