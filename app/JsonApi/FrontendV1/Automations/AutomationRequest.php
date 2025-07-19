<?php

namespace App\JsonApi\FrontendV1\Automations;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class AutomationRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:1'
            ],
            'position' => [
                'sometimes',
                'integer'
            ],
            'is_enabled' => [
                'sometimes',
                'boolean'
            ],
            'applies_to' => [
                'sometimes',
                'required',
                'string'
            ]
        ];
    }
}
