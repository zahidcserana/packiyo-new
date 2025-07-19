<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\FormRequest;
use App\Rules\DistinctRateCardsRule;

class UpdateRateCardsRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'primary_rate_card_id' => [
                'nullable',
                'exists:rate_cards,id,deleted_at,NULL'
            ],
            'secondary_rate_card_id' => [
                'nullable',
                'exists:rate_cards,id,deleted_at,NULL',
                new DistinctRateCardsRule
            ]
        ];
    }
}
