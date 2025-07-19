<?php

namespace App\JsonApi\PublicV1\PurchaseOrders;

use App\JsonApi\PublicV1\Server;
use App\Models\PurchaseOrder;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Map;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Filters\WhereNotNull;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class PurchaseOrderSchema extends Schema
{

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = PurchaseOrder::class;

    protected ?array $defaultPagination = [
        'number' => 1,
        'size' => Server::DEFAULT_PAGE_SIZE
    ];

    protected int $maxDepth = 2;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make(),
            BelongsTo::make('customer')->readOnlyOnUpdate(),
            BelongsTo::make('warehouse')->readOnlyOnUpdate(),
            Str::make('number'),
            Str::make('warehouse_name')->hidden(),
            Str::make('supplier_name')->hidden(),
            Str::make('notes'),
            Str::make('external_id'),
            Number::make('tracking_number'),
            Str::make('tracking_url'),
            Str::make('tags')
                ->serializeUsing(static function ($value) {
                    return $value->pluck('name')->join(', ');
                }),
            DateTime::make('ordered_at'),
            DateTime::make('expected_at'),
            Boolean::make('priority')->readOnly(),
            DateTime::make('delivered_at')->readOnly(),
            DateTime::make('received_at')->readOnly(),
            DateTime::make('closed_at')->readOnly(),
            HasMany::make('purchase_order_items'),
            DateTime::make('created_at')->sortable()->readOnly(),
            DateTime::make('updated_at')->sortable()->readOnly(),
            Map::make('purchase_order_items_data', [])->hidden(),
        ];
    }

    /**
     * Get the resource filters.
     *
     * @return array
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            WhereIn::make('customer', 'customer_id')->delimiter(','),
            Where::make('number'),
            Where::make('created_at_min', 'created_at')->gte(),
            Where::make('created_at_max', 'created_at')->lte(),
            Where::make('received_at_min', 'received_at')->gte(),
            Where::make('received_at_max', 'received_at')->lte(),
            WhereNotNull::make('received', 'received_at'),
            Where::make('closed_at_min', 'closed_at')->gte(),
            Where::make('closed_at_max', 'closed_at')->lte(),
            WhereNotNull::make('closed', 'closed_at'),
        ];
    }

    /**
     * Get the resource paginator.
     *
     * @return Paginator|null
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

}
