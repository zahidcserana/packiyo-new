<?php

namespace App\JsonApi\PublicV1\Returns;

use App\Models\Return_;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOne;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class ReturnSchema extends Schema
{

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Return_::class;

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
            BelongsTo::make('order')->readOnly(),
            HasOne::make('warehouse')->readOnly(),
            HasMany::make('return_items', 'items')->type('return-items')->readOnly(),
            Str::make('status_text')
                ->extractUsing(static function (Return_ $return) {
                    return $return->getStatusText();
                })
                ->readOnly(),
            Str::make('number')->readOnly(),
            Str::make('approved')->readOnly(),
            Str::make('reason')->readOnly(),
            Str::make('notes')->readOnly(),
            Number::make('weight')->readOnly(),
            Number::make('height')->readOnly(),
            Number::make('length')->readOnly(),
            Number::make('width')->readOnly(),
            DateTime::make('requested_at')->readOnly(),
            DateTime::make('expected_at')->readOnly(),
            DateTime::make('received_at')->readOnly(),
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
