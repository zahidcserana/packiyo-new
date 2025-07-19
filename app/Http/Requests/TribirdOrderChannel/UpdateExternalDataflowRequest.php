<?php

namespace App\Http\Requests\TribirdOrderChannel;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExternalDataflowRequest extends FormRequest
{
    public static function validationRules(): array
    {
        $customerId = static::getInputField('customer_id');

        return [
            'name' => Rule::unique('order_channels')->where(function ($query) use ($customerId) {
                return $query->where('customer_id', $customerId);
            })
        ];
    }
}
