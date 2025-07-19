<?php

namespace App\JsonApi\FrontendV1\UserSettings;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class UserSettingRequest extends ResourceRequest
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
            'user' => JsonApiRule::toOne()
        ];
    }

}
