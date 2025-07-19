<?php

namespace App\Http\Requests\LocationType;

use App\Http\Requests\FormRequest;

class StoreRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'customer_id' => [
                'required',
                'exists:customers,id,deleted_at,NULL'
            ],
            'name' => [
                'required'
            ],
            'pickable' => [
                'nullable'
            ],
            'sellable' => [
                'nullable'
            ],
            'bulk_ship_pickable' => [
                'nullable'
            ],
            'disabled_on_picking_app' => [
                'nullable'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'The customer field is required',
            'name.required' => 'The name field is required'
        ];
    }
}
