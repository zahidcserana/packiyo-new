<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\ContactInformation\StoreRequest as ContactInformationStoreRequest;
use App\Http\Requests\FormRequest;
use App\Http\Requests\OrderItem\StoreRequest as OrderItemStoreRequest;
use App\Models\Order;
use App\Models\OrderChannel;
use App\Models\OrderStatus;
use App\Models\ShippingBox;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Rules\BelongsToCustomer;
use App\Rules\ExistsOrStaticValue;
use App\Rules\UniqueForCustomer;
use App\Rules\UniqueOrderNumber;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public static function validationRules(): array
    {
        $customerId = static::getInputField('customer_id');
        $orderChannelId = static::getInputField('order_channel_id');

        $rules = [
            'customer_id' => [
                'sometimes',
                'required',
                'exists:customers,id,deleted_at,NULL'
            ],
            'order_channel_id' => [
                'nullable',
                new BelongsToCustomer(OrderChannel::class, $customerId)
            ],
            'external_id' => [
                'sometimes',
                new UniqueForCustomer(Order::class, $customerId)
            ],
            'order_status_id' => [
                'sometimes',
                new ExistsOrStaticValue('order_statuses', 'id', 'pending'),
                new BelongsToCustomer(OrderStatus::class, $customerId, 'pending')
            ],
            'number' => [
                'nullable',
                'string',
                new UniqueOrderNumber($customerId, $orderChannelId)
            ],
            'ordered_at' => [
                'sometimes',
            ],
            'hold_until' => [
                'sometimes',
                'nullable',
            ],
            'ship_before' => [
                'sometimes',
                'nullable',
            ],
            'scheduled_delivery' => [
                'sometimes',
                'nullable',
            ],
            'slip_note' => [
                'sometimes'
            ],
            'packing_note' => [
                'sometimes'
            ],
            'internal_note' => [
                'sometimes'
            ],
            'gift_note' => [
                'sometimes'
            ],
            'append_slip_note' => [
                'sometimes'
            ],
            'append_packing_note' => [
                'sometimes'
            ],
            'append_internal_note' => [
                'sometimes'
            ],
            'append_gift_note' => [
                'sometimes'
            ],
            'tags' => [
                'sometimes',
            ],
            'fraud_hold' => [
                'sometimes',
                'boolean'
            ],
            'allocation_hold' => [
                'sometimes',
                'boolean'
            ],
            'address_hold' => [
                'sometimes',
                'boolean'
            ],
            'payment_hold' => [
                'sometimes',
                'boolean'
            ],
            'operator_hold' => [
                'sometimes',
                'boolean'
            ],
            'is_wholesale' => [
                'sometimes',
                'boolean'
            ],
            'priority' => [
                'sometimes',
                'integer'
            ],
            'disabled_on_picking_app' => [
                'sometimes',
                'integer'
            ],
            'allow_partial' => [
                'sometimes',
                'integer'
            ],
            'shipping' => [
                'sometimes',
                'numeric'
            ],
            'tax' => [
                'sometimes',
                'numeric'
            ],
            'discount' => [
                'sometimes',
                'numeric'
            ],
            'shipping_method_id' => [
                'sometimes',
                'required_without:shipping_method_name',
                'nullable',
                new ExistsOrStaticValue('shipping_methods', 'id', 'generic'),
            ],
            'incoterms' => [
                'nullable'
            ],
            'shipping_method_name' => [
                'sometimes',
                'required_without:shipping_method_id'
            ],
            'shipping_method_code' => [
                'sometimes'
            ],
            'order_items' => [
                'required',
                'array',
            ],
            'order_items.*.cancelled' => [
                'sometimes',
            ],
            'order_items.*.is_kit_item' => [
                'sometimes',
            ],
            'order_items.*.child_count' => [
                'sometimes',
            ],
            'order_items.*.child_quantity' => [
                'sometimes',
            ],
            'order_items.*.parent_product_id' => [
                'sometimes',
            ],
            'currency_id' => [
                'nullable',
                'exists:currencies,id'
            ],
            'currency_code' => [
                'nullable',
                'exists:currencies,code'
            ],
            'custom_invoice_url' => [
                'nullable'
            ],
            'shipping_box_id' => [
                'sometimes',
                'nullable',
                'exists:shipping_boxes,id',
                new BelongsToCustomer(ShippingBox::class, $customerId)
            ],
            'warehouse_id' => [
                'sometimes',
                new BelongsToCustomer(Warehouse::class, $customerId)
            ],
            'order_type' => [
                'sometimes',
                Rule::in(array_keys(Order::ORDER_TYPES))
            ],
            'shipping_warehouse_id' => [
                'required_if:order_type,transfer',
                new BelongsToCustomer(Warehouse::class, $customerId),
                'different:warehouse_id'
            ],
            'shipping_vendor_id' => [
                'nullable',
                new BelongsToCustomer(Supplier::class, $customerId)
            ],
            'delivery_confirmation' => [
                'sometimes',
                'nullable'
            ],
            'order_channel_payload' => [
                'sometimes'
            ],
            'handling_instructions' => [
                'nullable'
            ],
            'saturday_delivery' => [
                'nullable'
            ]
        ];

        OrderItemStoreRequest::$customerId = $customerId;

        $rules = array_merge_recursive($rules, OrderItemStoreRequest::prefixedValidationRules('order_items.*.'));

        $rules = array_merge_recursive($rules, ContactInformationStoreRequest::prefixedValidationRules('shipping_contact_information.'));

        $billingInformationRules = ContactInformationStoreRequest::prefixedValidationRules('billing_contact_information.');

        foreach ($billingInformationRules as $attribute => $billingContactInformationRules) {
            foreach ($billingContactInformationRules as $key => $rule) {
                if (str_starts_with($rule, 'required')) {
                    $billingInformationRules[$attribute][$key] = 'nullable';
                }
            }
        }

        return array_merge_recursive($rules, $billingInformationRules);
    }

    public function messages(): array
    {
        return [
            'shipping_warehouse_id.different' => 'Receiving and sending warehouses cannot be the same',
            'shipping_warehouse_id.required_if' => 'Please select a warehouse for transfer order',
            'shipping_vendor_id.*' => 'Please select a vendor for transfer order',
        ];
    }
}
