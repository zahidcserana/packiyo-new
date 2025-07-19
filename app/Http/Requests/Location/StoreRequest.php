<?php

namespace App\Http\Requests\Location;

use App\Http\Requests\FormRequest;
use App\Http\Requests\LocationProduct\StoreRequest as LocationProductStoreRequest;

class StoreRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'warehouse_id' => [
                'required',
                'exists:warehouses,id,deleted_at,NULL'
            ],
            'name' => [
                'required'
            ],
            'pickable' => [
                'sometimes'
            ],
            'bulk_ship_pickable' => [
                'sometimes'
            ],
            'disabled_on_picking_app' => [
                'sometimes'
            ],
            'sellable' => [
                'sometimes'
            ],
            'is_receiving' => [
                'sometimes'
            ],
            'location_type_id' => [
                'nullable'
            ],
            'priority_counting_requested_at' => [
                'sometimes'
            ],
            'barcode' => [
                'sometimes'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required',
        ];
    }
}
