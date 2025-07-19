<?php

namespace App\JsonApi\PublicV1\Webhooks;

use App\Http\Requests\Webhook\StoreRequest;
use App\Http\Requests\Webhook\UpdateRequest;
use App\Models\Webhook;
use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class WebhookRequest extends ResourceRequest
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
        $rules['customer'] = JsonApiRule::toOne();

        return $rules;
    }


    protected function prepareForValidation()
    {
        parent::prepareForValidation();

        $input = $this->input();

        $operation = ucfirst($this->input('data.attributes.operation'));

        $input['data']['attributes']['operation'] = in_array($operation, Webhook::OPERATION_TYPES) ? $operation : Webhook::OPERATION_TYPE_STORE;
        $input['data']['attributes']['secret_key'] = 'secret';

        $customerId = $this->input('data.relationships.customer.data.id');
        $input['data']['attributes']['customer_id'] = $customerId;

        if (isset($input['data']['attributes']['object_type'])) {
            $input['data']['attributes']['object_type'] = 'App\\Models\\' . $input['data']['attributes']['object_type'];
        }

        $this->replace($input);
    }

    protected function withExisting(Webhook $model, array $existing)
    {
        if (isset($existing['attributes']['object_type'])) {
            $existing['attributes']['object_type'] = 'App\\Models\\' . $existing['attributes']['object_type'];
        }

        return $existing;
    }
}
