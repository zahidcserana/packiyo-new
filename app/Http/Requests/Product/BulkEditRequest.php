<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\FormRequest;

class BulkEditRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'ids' => [
                'required'
            ],
            'add_tags' => [
                'nullable',
                'array'
            ],
            'remove_tags' => [
                'nullable',
                'array'
            ],
            'lot_tracking' => [
                'nullable',
                'integer'
            ],
            'inventory_sync' => [
                'nullable',
                'integer'
            ],
            'priority_counting_requested_at' => [
                'nullable',
                'integer'
            ],
            'has_serial_number' => [
                'nullable',
                'integer'
            ],
            'remove_empty_locations' => [
                'nullable',
                'integer'
            ],
            'hs_code' => [
                'nullable',
                'string'
            ],
            'customs_description' => [
                'nullable',
                'string'
            ],
            'customs_price' => [
                'nullable',
                'numeric'
            ],
            'notes' => [
                'nullable',
                'string'
            ],
            'reorder_threshold' => [
                'nullable',
                'integer'
            ],
            'quantity_reorder' => [
                'nullable',
                'integer'
            ],
            'quantity_reserved' => [
                'nullable',
                'integer'
            ],
            'warehouse_id' => [
                'nullable',
                'exists:warehouses,id,deleted_at,NULL'
            ],
            'country_id' => [
                'nullable',
                'integer'
            ],
            'vendor_id' => [
                'nullable',
                'integer'
            ]
        ];
    }
}
