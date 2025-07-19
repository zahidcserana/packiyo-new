<?php

namespace App\JsonApi\PublicV1\Customers;

use App\JsonApi\PublicV1\Server;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class CustomerCollectionQuery extends ResourceQuery
{
    protected ?array $defaultIncludePaths = [
        'contact_information',
        'children',
        'children.contact_information',
        'customer_settings'
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
