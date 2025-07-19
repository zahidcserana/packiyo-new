<?php

namespace App\Http\Requests\Shipment;

use App\Http\Requests\FormRequest;

class ShipmentTrackingRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'id' => [
                'nullable',
                'exists:shipment_trackings,id'
            ],
            'shipment_id' => [
                'required',
                'exists:shipments,id'
            ],
            'shipping_method_name' => [
                'sometimes'
            ],
            'tracking_number' => [
                'sometimes'
            ],
            'tracking_url' => [
                'sometimes'
            ],
            'type' => [
                'sometimes'
            ],
        ];
    }
}
