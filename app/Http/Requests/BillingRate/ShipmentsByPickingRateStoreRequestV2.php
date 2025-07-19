<?php

namespace App\Http\Requests\BillingRate;

use App\Http\Requests\FormRequest;
use App\Models\BillingRate;
use App\Rules\BillingRates\ShipmentsByPickingRateRule;

class ShipmentsByPickingRateStoreRequestV2 extends FormRequest
{
    public static function validationRules()
    {
        $rateCard = request()->route('rate_card');
        $type = BillingRate::SHIPMENTS_BY_PICKING_RATE_V2;
        $billingRate = request()->route('billing_rate') ?? null;

        return array_merge(
            [
                'is_enabled' => [
                    'sometimes'
                ],
                'name' => [
                    'required'
                ],
                'code' => [
                    'sometimes'
                ],
                'settings' => [
                    'required',
                    new ShipmentsByPickingRateRule($rateCard, $type, $billingRate)
                ],
            ]
        );
    }
}
