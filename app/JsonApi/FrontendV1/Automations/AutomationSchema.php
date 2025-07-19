<?php

namespace App\JsonApi\FrontendV1\Automations;

use App\JsonApi\PublicV1\Server;
use App\Models\Automation;
use App\Models\Automations\IdentifiesUsingSlugs;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class AutomationSchema extends Schema
{
    use IdentifiesUsingSlugs;

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Automation::class;

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
            Str::make('name')->sortable(),
            Boolean::make('is_enabled'),
            Str::make('applies_to')->sortable(),
            Number::make('position')->sortable(),
            Number::make('order')->sortable(),
            ArrayList::make('target_events')->serializeUsing(
                static fn (mixed $value) => static::serializeArrayUsing($value)
            ),
            BelongsTo::make('customer')->readOnlyOnUpdate(),
            HasMany::make('applies_to_customers')->type('customers'),
            HasMany::make('conditions')->type('automation-conditions'),
            HasMany::make('actions')->type('automation-actions'),
            DateTime::make('created_at')->sortable()->readOnly(),
            DateTime::make('updated_at')->sortable()->readOnly()
        ];
    }

    protected static function serializeArrayUsing(mixed $value): array
    {
        return array_map(fn (string $eventClass) => static::classToSlug($eventClass), $value);
    }

    /**
     * Get the resource filters.
     *
     * @return array
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this)
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
