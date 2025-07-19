<?php

namespace App\Http\Requests\BillingRate;

use App\Http\Requests\FormRequest;
use App\Models\BillingRate;
use App\Rules\BillingRates\StorageByLocationRule;

class StorageByLocationStoreRequest extends FormRequest
{
    public static function validationRules()
    {
        $rateCard = request()->route('rate_card');
        $type = BillingRate::STORAGE_BY_LOCATION;
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
                    new StorageByLocationRule($rateCard, $type, $billingRate)
                ],
            ]
        );
    }
}
