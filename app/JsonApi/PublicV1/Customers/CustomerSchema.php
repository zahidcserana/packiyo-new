<?php

namespace App\JsonApi\PublicV1\Customers;

use App\JsonApi\Filters\WhereLike;
use App\JsonApi\Filters\WhereNotAssignedToAutomation;
use App\JsonApi\PublicV1\Server;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOne;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
class CustomerSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Customer::class;

    protected bool $selfLink = false;

    protected ?array $defaultPagination = [
        'number' => 1,
        'size' => Server::DEFAULT_PAGE_SIZE
    ];

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make(),
            HasOne::make('contact_information')->readOnly(),
            HasMany::make('customer_settings')->readOnly(),
            HasMany::make('children')->type('customers')->readOnly(),
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
            WhereLike::make('name', 'contactInformation.name', true),
            WhereNotAssignedToAutomation::make('no-automation','id'),
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

    /**
     * Build an "index" query for the given resource.
     *
     * @param Request|null $request
     * @param Builder $query
     * @return Builder
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        $accessToken = request()->user()->currentAccessToken();

        if ($accessToken && $accessToken->customer_id) {
            return $query->withClients($accessToken->customer_id);
        }

        return $query->whereIn('id', app('user')->getCustomers()->pluck('id'));
    }
}
