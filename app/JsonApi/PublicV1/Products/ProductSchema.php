<?php

namespace App\JsonApi\PublicV1\Products;

use App\JsonApi\PublicV1\Server;
use App\Models\Product;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Map;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\OnlyTrashed;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\SoftDeletes;
use Webpatser\Countries\Countries;

class ProductSchema extends Schema
{
    use SoftDeletes;

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Product::class;

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
            HasMany::make('barcodes', 'productBarcodes')->readOnlyOnUpdate(),
            BelongsToMany::make('product_images')->readOnly()->type('product-images'),
            BelongsToMany::make('location_products')->readOnly()->type('location-products'),
            BelongsToMany::make('kits')->readOnly()->type('kit-items'),
            HasMany::make('components')->readOnly()->type('kit-items'),
            Str::make('sku'),
            Str::make('name'),
            Map::make('product_barcodes', [])->hidden(),
            Str::make('type')
                ->serializeUsing(fn($value) => str_replace('static_kit', 'kit', $value))
                ->readOnly(),
            Number::make('price'),
            Number::make('value'),
            Number::make('customs_price'),
            Str::make('hs_code'),
            Number::make('country_of_origin')
                ->serializeUsing(static function ($value) {
                    return $value && is_numeric($value) ? Countries::find($value)->iso_3166_2 : null;
                })
                ->deserializeUsing(static function ($value) {
                    return $value && !is_numeric($value) ? Countries::where('iso_3166_2', $value)->first()->id : $value;
                }),
            Str::make('notes'),
            Number::make('width'),
            Number::make('height'),
            Number::make('length'),
            Number::make('weight'),
            Str::make('barcode'),
            Str::make('customs_description'),
            Str::make('tags')
                ->serializeUsing(static function ($value) {
                    return $value->pluck('name')->join(', ');
                }),
            Boolean::make('inventory_sync'),
//            Str::make('location_id')->hidden(),
            Number::make('quantity_on_hand')->readOnly(),
            Number::make('quantity_allocated')->readOnly(),
            Number::make('quantity_available')->readOnly(),
            Number::make('quantity_backordered')->readOnly(),
            Map::make('product_image_data', [])->hidden(),
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
            WhereIn::make('customer', 'customer_id')->delimiter(','),
            Where::make('sku'),
            Where::make('name'),
            OnlyTrashed::make('archived')
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
