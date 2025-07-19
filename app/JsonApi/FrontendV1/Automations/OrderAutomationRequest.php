<?php

namespace App\JsonApi\FrontendV1\Automations;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class OrderAutomationRequest extends ResourceRequest
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
                'string'
            ],
            'applies_to' => [
                'sometimes',
                'required',
                'string'
            ]
        ];
    }
}
