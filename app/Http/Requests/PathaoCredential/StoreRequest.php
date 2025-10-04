<?php

namespace App\Http\Requests\PathaoCredential;

use App\Http\Requests\FormRequest;
use Illuminate\Support\Str;

class StoreRequest extends FormRequest
{
    public static function validationRules()
    {
        return [
            'customer_id' => [
                'sometimes',
                'exists:customers,id'
            ],
            'api_base_url' => [
                'required',
                'url'
            ],
            'store_id' => [
                'required',
                'integer'
            ],
            'client_id' => [
                'required'
            ],
            'client_secret' => [
                'required'
            ],
            'username' => [
                'required'
            ],
            'password' => [
                'required'
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $apiBaseUrl = $this->api_base_url;

        if (!Str::startsWith($apiBaseUrl, 'http')) {
            $this->merge([
                'api_base_url' => 'https://' . $apiBaseUrl . '/aladdin/api/v1/'
            ]);
        }
    }
}
