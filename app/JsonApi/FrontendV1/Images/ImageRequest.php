<?php

namespace App\JsonApi\FrontendV1\Images;

use App\Http\Requests\Image\UpdateRequest;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class ImageRequest extends ResourceRequest
{

    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        $request = new UpdateRequest($this->input('data.attributes'));

        $rules = $request->rules();

        unset($rules['user_id']);
        $rules['user'] = JsonApiRule::toOne();

        return $rules;
    }

    protected function prepareForValidation()
    {
        parent::prepareForValidation();

        $input = $this->input();

        $this->replace($input);
    }

}
