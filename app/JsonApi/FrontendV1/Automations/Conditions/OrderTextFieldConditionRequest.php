<?php

namespace App\JsonApi\FrontendV1\Automations\Conditions;

use LaravelJsonApi\Validation\Rule as JsonApiRule;

class OrderTextFieldConditionRequest extends AutomationConditionRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'case_sensitive' => [
                'required',
                'bool'
            ],
            'comparison_operator' => [
                'required',
                'string'
            ],
            'field_name' => [
                'required',
                'string'
            ],
            'text_field_values' => [
                'required',
                'array'
            ],
            'automation' => JsonApiRule::toOne()
        ]);
    }
}
