<?php

namespace App\Http\Requests\Invoice;

use App\Http\Requests\FormRequest;

class AdHocBillingRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'billing_rate_id' => [
                'required',
                'exists:billing_rates,id,deleted_at,NULL'
            ],
            'quantity' => [
                'required',
                'numeric',
                'min:0'
            ],
            'period_end' => [
                'required'
            ]
        ];
    }
}
