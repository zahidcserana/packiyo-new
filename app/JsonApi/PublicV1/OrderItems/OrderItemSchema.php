<?php

namespace App\JsonApi\PublicV1\OrderItems;

use App\Models\OrderItem;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class OrderItemSchema extends Schema
{

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = OrderItem::class;

    protected bool $selfLink = false;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make(),
            BelongsTo::make('order', 'order')->readOnly(),
            BelongsTo::make('product', 'product')->readOnly(),
            HasMany::make('return_items')->readOnly(),
            Str::make('sku'),
            Str::make('name'),
            Number::make('price'),
            Number::make('quantity'),
            Number::make('quantity_pending'),
            Number::make('quantity_shipped'),
            Number::make('quantity_reshipped'),
            Number::make('quantity_returned'),
            Number::make('quantity_allocated'),
            Number::make('quantity_allocated_pickable'),
            Number::make('quantity_backordered'),
            Str::make('external_id'),
            DateTime::make('created_at')->sortable()->readOnly(),
            DateTime::make('updated_at')->sortable()->readOnly(),
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
            Where::make('sku'),
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
