<?php

namespace App\JsonApi\PublicV1\Totes;

use App\Http\Requests\Tote\StoreRequest;
use App\Http\Requests\Tote\UpdateRequest;
use App\Models\Tote;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class ToteRequest extends ResourceRequest
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

        unset($rules['warehouse_id']);
        $rules['warehouse'] = JsonApiRule::toOne();

        return $rules;
    }

    protected function prepareForValidation()
    {
        parent::prepareForValidation();

        $input = $this->input();

        $warehouseId = $this->input('data.relationships.warehouse.data.id');

        if (!$warehouseId) {
            $tote = Tote::find($input['data']['id']);
            $warehouseId = $tote->warehouse_id;
        }

        $input['data']['attributes']['warehouse_id'] = $warehouseId;

        $this->replace($input);
    }
}
