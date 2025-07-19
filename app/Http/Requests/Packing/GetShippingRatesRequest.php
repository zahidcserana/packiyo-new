<?php

namespace App\Http\Requests\Packing;

use App\Http\Requests\ContactInformation\StoreRequest as ContactInformationStoreRequest;
use App\Http\Requests\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Log;

class GetShippingRatesRequest extends FormRequest
{
    public static function validationRules(): array
    {
        $contactInformationRules = ContactInformationStoreRequest::prefixedValidationRules('shipping_contact_information.');

        foreach ($contactInformationRules as $attribute => $rule) {
            if (str_contains($attribute, 'zip') || str_contains($attribute, 'country_id')) {
                $contactInformationRules[$attribute] = [
                    'required'
                ];
            }
        }

        return array_merge(
            [
                'packing_state' => [
                    'required',
                    'string'
                ],
                'total_unpacked_items' => [
                    'numeric'
                ],
                'total_unpacked_weight' => [
                    'numeric'
                ]
            ],
            $contactInformationRules
        );
    }

    public function messages(): array
    {
        return [
            'shipping_contact_information.country_id.required' => __('The country field is required for the shipping contact information'),
            'shipping_contact_information.zip.required' => __('ZIP code is required for the shipping contact information')
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        Log::info(__CLASS__, request()->all());

        parent::failedValidation($validator);
    }
}
