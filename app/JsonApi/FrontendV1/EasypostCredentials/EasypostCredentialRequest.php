<?php

namespace App\JsonApi\FrontendV1\EasypostCredentials;

use App\Http\Requests\EasypostCredential\StoreRequest;
use App\Http\Requests\EasypostCredential\UpdateRequest;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class EasypostCredentialRequest extends ResourceRequest
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

        $useNativeTrackingUrls = $attributes['use_native_tracking_urls'] ?? false;

        $attributes['use_native_tracking_urls'] = filter_var($useNativeTrackingUrls, FILTER_VALIDATE_BOOLEAN);

        $input['data']['attributes'] = $attributes;

        $this->replace($input);
    }

}
