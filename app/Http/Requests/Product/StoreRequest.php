<?php

namespace App\Http\Requests\Product;

use App\Enums\LotPriority;
use App\Features\PreventDuplicateBarcodes;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Image\ImageRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Rules\IsDuplicateBarcode;
use App\Rules\UniqueForCustomer;
use Illuminate\Validation\Rules\Enum;
use Laravel\Pennant\Feature;

class StoreRequest extends FormRequest
{
    public static function validationRules(): array
    {
        $customerId = static::getInputField('customer_id');

        $rules = [
            'sku' => [
                'required',
                new UniqueForCustomer(Product::class, $customerId)
            ],
            'name' => [
                'required',
            ],
            'price' => [
                'nullable',
                'numeric'
            ],
            'cost' => [
                'nullable',
                'numeric'
            ],
            'notes' => [
                'sometimes'
            ],
            'customer_id' => [
                'required',
                'exists:customers,id'
            ],
            'width' => [
                'nullable',
                'numeric'
            ],
            'height' => [
                'nullable',
                'numeric'
            ],
            'length' => [
                'nullable',
                'numeric'
            ],
            'weight' => [
                'nullable',
                'numeric'
            ],
            'barcode' => [
                'nullable'
            ],
            'suppliers' => [
                'sometimes',
                'array',
            ],
            'suppliers.*' => [
                'required',
                'integer'
            ],
            'lot_tracking' => [
                'sometimes',
            ],
            'lot_without_expiration' => [
                'sometimes'
            ],
            'lot_priority' => [
                'nullable',
                new Enum(LotPriority::class),
            ],
            'inventory_sync' => [
                'sometimes'
            ],
            'value' => [
                'nullable',
                'numeric'
            ],
            'customs_price' => [
                'nullable',
                'numeric'
            ],
            'customs_description' => [
                'nullable',
                'string'
            ],
            'hs_code' => [
                'nullable',
                'string'
            ],
            'country_of_origin' => [
                'nullable',
                'exists:countries,id'
            ],
            'country_of_origin_code' => [
                'nullable',
            ],
            'update_vendor' => [
                'sometimes',
            ],
            'product_barcodes' => [
                'array'
            ],
            'file.*' => [
                'sometimes'
            ],
            'priority_counting_requested_at' => [
                'sometimes'
            ],
            'has_serial_number' => [
                'sometimes'
            ],
            'tags' => [
                'sometimes'
            ],
            'reorder_threshold' => [
                'nullable',
                'numeric'
            ],
            'quantity_reorder' => [
                'nullable',
                'numeric'
            ],
            'quantity_reserved' => [
                'nullable'
            ],
            'hazmat' => [
                'nullable'
            ],
            'type' => [
                'sometimes'
            ],
            'kit_type' => [
                'sometimes'
            ],
            'is_kit' => [
                'sometimes'
            ]
        ];

        if (self::getInputField('kit_items')) {
            $rules = array_merge($rules, [
                'kit_items' => [
                    'required_if:type,dynamic_kit,static_kit',
                ],
                'kit_items.*' => [
                    'required_if:type,dynamic_kit,static_kit',
                ],
                'kit_items.*.id' => [
                    'required_if:type,dynamic_kit,static_kit',
                    'integer',
                    'exists:products,id,type,regular'
                ],
                'kit_items.*.quantity' => [
                    'required_if:type,dynamic_kit,static_kit'
                ],
                'kit-quantity.*' => [
                    'sometimes',
                ],
            ]);
        }

        $customer = Customer::find($customerId);

        if (Feature::for($customer)->active(PreventDuplicateBarcodes::class) || Feature::for('instance')->active(PreventDuplicateBarcodes::class)) {
            $rules['barcode'][] = new IsDuplicateBarcode($customerId);
        }

        return array_merge_recursive($rules, ImageRequest::prefixedValidationRules('product_images.*.'));
    }

    public function attributes(): array
    {
        return [
            'sku' => 'SKU',
            'name' => 'NAME',
            'price' => 'PRICE',
            'cost' => 'COST',
            'notes' => 'NOTES',
            'width' => 'WIDTH',
            'height' => 'HEIGHT',
            'length' => 'LENGTH',
            'weight' => 'WEIGHT',
            'barcode' => 'BARCODE',
            'suppliers' => 'SUPPLIERS',
            'is_kit' => 'IS KIT',
            'kit_items' => 'KIT ITEMS',
            'type' => 'PRODUCT TYPE',
            'kit_type' => 'KIT TYPE'
        ];
    }
}
