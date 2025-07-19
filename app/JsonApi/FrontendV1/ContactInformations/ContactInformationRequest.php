<?php

namespace App\JsonApi\FrontendV1\ContactInformations;

use App\Http\Requests\ContactInformation\StoreRequest;
use App\Http\Requests\ContactInformation\UpdateRequest;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class ContactInformationRequest extends ResourceRequest
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

        $rules = $request->rules();

        unset($rules['customer_id']);
        $rules['customer_id'] = JsonApiRule::toOne();

        return $rules;
    }

    protected function prepareForValidation()
    {
        parent::prepareForValidation();

        $input = $this->input();

        $this->replace($input);
    }

}
