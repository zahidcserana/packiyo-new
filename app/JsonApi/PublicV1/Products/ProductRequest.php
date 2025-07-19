<?php

namespace App\JsonApi\PublicV1\Products;

use App\Http\Requests\Product\StoreRequest;
use App\Http\Requests\Product\UpdateRequest;
use App\Models\Product;
use Illuminate\Support\Arr;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;
use Webpatser\Countries\Countries;

class ProductRequest extends ResourceRequest
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

        $customerId = $this->input('data.relationships.customer.data.id');
        $input['data']['attributes']['customer_id'] = $customerId;

        $countryCode = $this->input('data.attributes.country_of_origin');

        if ($countryCode && ($country = Countries::where('iso_3166_2', $countryCode)->first())) {
            $input['data']['attributes']['country_of_origin'] = $country->id;
        }

        if ($tagsArray = $this->setTags(Arr::get($input, 'data', []))) {
            $input['data']['attributes']['tags'] = $tagsArray;
        }

        if ($productImageData = Arr::get($input, 'data.attributes.product_image_data')) {
            $input['data']['attributes']['product_images'] = $productImageData;
            unset($input['data']['attributes']['product_image_data']);
        }

        $this->replace($input);
    }

    protected function withExisting(Product $model, array $existing)
    {
        if ($countryOfOrigin = Arr::get($existing, 'attributes.country_of_origin')) {
            $existing['attributes']['country_of_origin'] = Countries::where('iso_3166_2', $countryOfOrigin)->first()->id;
        }

        if ($tagsArray = $this->setTags($existing)) {
            $existing['attributes']['tags'] = $tagsArray;
        }

        return $existing;
    }

    /**
     * @param array $input
     * @return array|null
     */
    private function setTags(array $input): array|null
    {
        $tags = Arr::get($input, 'attributes.tags');

        if ($tags != '') {
            return array_map('trim', explode(',', $tags));
        }

        return null;
    }
}
