<?php

namespace App\JsonApi\PublicV1\Orders;

use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class OrderQuery extends ResourceQuery
{

    protected ?array $defaultIncludePaths = [
        'customer',
        'customer.contact_information',
        'billing_contact_information',
        'order_items',
        'order_items.product.product_images',
        'order_channel',
        'returns.return_items.order_item',
        'shipping_method',
        'shipping_method.shipping_carrier',
        'shipments',
        'shipments.contact_information',
        'shipments.shipment_items.order_item',
        'shipments.shipment_labels',
        'shipments.shipping_method.shipping_carrier',
        'shipments.shipment_trackings',
        'shipments.packages.package_order_items',
        'shipments.packages.package_order_items.lot',
        'shipping_contact_information',
        'shipping_box',
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
                JsonApiRule::filter()->forget('id'),
            ],
            'include' => [
                'nullable',
                'string',
                JsonApiRule::includePaths(),
            ],
            'page' => JsonApiRule::notSupported(),
            'sort' => JsonApiRule::notSupported(),
            'withCount' => [
                'nullable',
                'string',
                JsonApiRule::countable(),
            ],
        ];
    }
}
