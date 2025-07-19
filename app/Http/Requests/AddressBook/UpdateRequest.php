<?php

namespace App\Http\Requests\AddressBook;

use App\Http\Requests\FormRequest;

class UpdateRequest extends FormRequest
{
    public static function validationRules(): array
    {
        $rules = StoreRequest::validationRules();

        $rules['id'] = ['required', 'exists:address_books,id,deleted_at,NULL'];
        unset($rules['customer_id']);

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
