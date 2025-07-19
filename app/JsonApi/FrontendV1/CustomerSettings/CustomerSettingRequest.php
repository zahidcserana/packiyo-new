<?php

namespace App\JsonApi\FrontendV1\CustomerSettings;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class CustomerSettingRequest extends ResourceRequest
{

    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'key' => ['string'],
            'value' => ['string'],
            'customer' => JsonApiRule::toOne()
        ];
    }

}
