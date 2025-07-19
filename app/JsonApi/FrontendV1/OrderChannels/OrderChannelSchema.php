<?php

namespace App\JsonApi\FrontendV1\OrderChannels;

use App\Models\OrderChannel;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class OrderChannelSchema extends Schema
{

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = OrderChannel::class;

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
            Str::make('name')->sortable(),
            Str::make('settings')->sortable(),
            Str::make('image_url', 'image_url')
                ->serializeUsing(fn ($value) => $this->serializeImageUrl($value)),
        ];
    }


    /**
     * Serialize the image URL field.
     *
     * @param string|null $value
     * @return string|null
     */
    protected function serializeImageUrl(?string $value): ?string
    {
        if (!\Illuminate\Support\Str::startsWith($value, config('tribird.base_url'))) {
            $value = config('tribird.base_url') . $value;
        }
        return $value;
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
