<?php

namespace App\JsonApi\PublicV1\ContactInformations;

use App\Models\ContactInformation;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Webpatser\Countries\Countries;

class ContactInformationSchema extends Schema
{

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = ContactInformation::class;

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
            Str::make('name'),
            Str::make('company_name'),
            Str::make('address'),
            Str::make('address2'),
            Str::make('city'),
            Str::make('state'),
            Str::make('zip'),
            Number::make('country', 'country_id')
                ->serializeUsing(static function ($value) {
                    return $value && is_numeric($value) ? Countries::find($value)->iso_3166_2 : null;
                })
                ->deserializeUsing(static function ($value) {
                    return $value && !is_numeric($value) ? Countries::where('iso_3166_2', $value)->first()->id : $value;
                }),
            Str::make('email'),
            Str::make('phone'),
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
