<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\ContactInformation\StoreRequest as ContactInformationStoreRequest;
use App\Http\Requests\FormRequest;
use App\Models\CustomerSetting;
use App\Rules\ExistsOrStaticValue;

class StoreRequest extends FormRequest
{
    public static function validationRules($includeContactInformationRules = true): array
    {
        $rules = [
            'id' => [
                'sometimes'
            ],
            'locale' => [
                'sometimes'
            ],
            'weight_unit' => [
                'sometimes'
            ],
            'dimension_unit' => [
                'sometimes'
            ],
            'order_slip_logo' => [
                'nullable'
            ],
            'threepl_logo' => [
                'nullable'
            ],
            'slug' => [
                'nullable'
            ],
            'store_domain' => [
                'nullable'
            ],
            'store_logo' => [
                'nullable'
            ],
            'currency' => [
                'nullable',
                'string',
            ],
            'ship_from_contact_information_id' => [
                'nullable',
                new ExistsOrStaticValue('contact_informations', 'id', 'none'),
            ],
            'return_to_contact_information_id' => [
                'nullable',
                new ExistsOrStaticValue('contact_informations', 'id', 'none'),
            ],
            'auto_return_label' => [
                'sometimes'
            ],
            'parent_customer_id' => [
                (auth()->user() && auth()->user()->isAdmin()) || app()->runningInConsole() ? 'nullable' : 'required',
                'exists:customers,id,deleted_at,NULL'
            ],
            'allow_child_customers' => [
                'sometimes'
            ]
        ];

        foreach (CustomerSetting::CUSTOMER_SETTING_KEYS as $key) {
            $rules[$key] = ['sometimes'];
        }

        if ($includeContactInformationRules) {
            $rules = array_merge_recursive($rules, ContactInformationStoreRequest::prefixedValidationRules('contact_information.'));

            $rules['contact_information.name'][0] = 'required';
            $rules['contact_information.country_id'][0] = 'required';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'contact_information.name.required' => 'The name field is required!',
            'contact_information.country_id.required' => 'Please select a country!'
        ];
    }
}
