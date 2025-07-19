<?php

namespace App\JsonApi\PublicV1\Products;

use App\JsonApi\PublicV1\Server;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class ProductCollectionQuery extends ResourceQuery
{

    protected ?array $defaultIncludePaths = [
        'customer',
        'customer.contact_information',
        'product_images',
        'barcodes'
    ];

    /**
     * Get the validation rules that apply to the request query parameters.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'fields' => [
                'nullable',
                'array',
                JsonApiRule::fieldSets(),
            ],
            'filter' => [
                'nullable',
                'array',
                JsonApiRule::filter(),
            ],
            'include' => [
                'nullable',
                'string',
                JsonApiRule::includePaths(),
            ],
            'page' => [
                'nullable',
                'array',
                JsonApiRule::page(),
            ],
            'page.number' => [
                'integer',
                'min:1'
            ],
            'page.size' => [
                'integer',
                'between:1,' . Server::MAX_PAGE_SIZE
            ],
            'sort' => [
                'nullable',
                'string',
                JsonApiRule::sort(),
            ],
            'withCount' => [
                'nullable',
                'string',
                JsonApiRule::countable(),
            ]
        ];
    }
}
