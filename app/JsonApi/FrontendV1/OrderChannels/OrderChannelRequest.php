<?php

namespace App\JsonApi\FrontendV1\OrderChannels;

use App\Http\Requests\OrderChannel\StoreRequest;
use App\Http\Requests\OrderChannel\UpdateRequest;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class OrderChannelRequest extends ResourceRequest
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
