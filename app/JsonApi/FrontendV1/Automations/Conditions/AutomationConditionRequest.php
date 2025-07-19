<?php

namespace App\JsonApi\FrontendV1\Automations\Conditions;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class AutomationConditionRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'position' => [
                'required',
                'integer'
            ],
        ];
    }
}
