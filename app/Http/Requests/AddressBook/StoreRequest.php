<?php

namespace App\Http\Requests\AddressBook;

use App\Http\Requests\ContactInformation\StoreRequest as ContactInformationStoreRequest;
use App\Http\Requests\FormRequest;

class StoreRequest extends FormRequest
{

    public static function validationRules($includeContactInformationRules = true): array
    {
        $rules = [
            'customer_id' => [
                'required',
                'exists:customers,id,deleted_at,NULL'
            ],
            'name' => [
                'required',
            ],
            'contact_information.name' => [
                'required',
            ],
        ];

        if ($includeContactInformationRules) {
            $rules = array_merge_recursive($rules, ContactInformationStoreRequest::prefixedValidationRules('contact_information.'));
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'contact_information.name.required' => 'The name field is required',
            'contact_information.address.required' => 'The address field is required',
            'contact_information.zip.required' => 'The ZIP field is required',
            'contact_information.city.required' => 'The City field is required',
        ];
    }
}
