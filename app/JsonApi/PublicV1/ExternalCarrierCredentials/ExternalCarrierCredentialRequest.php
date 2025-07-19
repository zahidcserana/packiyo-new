<?php

namespace App\JsonApi\PublicV1\ExternalCarrierCredentials;

use App\Http\Requests\ExternalCarrierCredential\StoreRequest;
use App\Http\Requests\ExternalCarrierCredential\UpdateRequest;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class ExternalCarrierCredentialRequest extends ResourceRequest
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

//        unset($rules['customer_id']);
        $rules['customer'] = JsonApiRule::toOne();

        return $rules;
    }

    protected function prepareForValidation()
    {
        parent::prepareForValidation();

        $input = $this->input();

        $customerId = $this->input('data.relationships.customer.data.id');

        $input['data']['attributes']['customer_id'] = $customerId;

        $this->replace($input);
    }
}
