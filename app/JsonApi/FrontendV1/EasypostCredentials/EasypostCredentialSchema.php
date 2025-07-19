<?php

namespace App\JsonApi\FrontendV1\EasypostCredentials;

use App\Models\EasypostCredential;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class EasypostCredentialSchema extends Schema
{

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = EasypostCredential::class;

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
            Str::make('customer_id')->sortable(),
            Str::make('api_key')->sortable(),
            Str::make('test_api_key')->sortable(),
            Str::make('commercial_invoice_signature')->sortable(),
            Str::make('commercial_invoice_letterhead')->sortable(),
            Str::make('endorsement')->sortable(),
            Boolean::make('use_native_tracking_urls')->sortable(),
            BelongsTo::make('customer')->readOnly(),
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
        ];
    }

    /**
     * Get the resource paginator.
     *
     * @return Paginator|null
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make()->withSimplePagination();
    }

}
