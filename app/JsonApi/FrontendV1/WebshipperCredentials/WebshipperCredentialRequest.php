<?php

namespace App\JsonApi\FrontendV1\WebshipperCredentials;

use App\Http\Requests\WebshipperCredential\StoreRequest;
use App\Http\Requests\WebshipperCredential\UpdateRequest;
use Illuminate\Support\Str;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class WebshipperCredentialRequest extends ResourceRequest
{

    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        if ($this->isCreating()) {
            $request = new StoreRequest($this->input('data.attributes'));
        } else {
            $request = new UpdateRequest($this->input('data.attributes'));
        }

        return $request->rules();
    }

    protected function prepareForValidation()
    {
        parent::prepareForValidation();

        $input = $this->input();

        $attributes = $input['data']['attributes'] ?? [];

        $apiBaseUrl = $attributes['api_base_url'] ?? null;

        if ($apiBaseUrl && !Str::startsWith($apiBaseUrl, 'http')) {
            $attributes['api_base_url'] = 'https://' . $apiBaseUrl . '.api.webshipper.io/v2/';
        }

        $input['data']['attributes'] = $attributes;

        $this->replace($input);
    }
}
