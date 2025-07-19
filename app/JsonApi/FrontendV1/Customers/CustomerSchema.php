<?php

namespace App\JsonApi\FrontendV1\Customers;

use App\Components\RouteOptimizationComponent;
use App\JsonApi\Filters\WhereLike;
use App\JsonApi\Filters\WhereNotAssignedToAutomation;
use App\Models\Customer;
use App\Models\EasypostCredential;
use App\Models\RateCard;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Map;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOne;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\NonEloquent\Filters\Filter;

class CustomerSchema extends Schema
{

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Customer::class;

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
            HasMany::make('user_settings')->readOnly(),
            Str::make('weight_units')->extractUsing(static function (Customer $customer) {
                return $customer::WEIGHT_UNITS;
            })->readOnly(),
            Str::make('dimensions')->extractUsing(static function (Customer $customer) {
                return $customer::DIMENSION_UNITS;
            })->readOnly(),
            Str::make('picking_route_strategies')->extractUsing(static function (Customer $customer) {
                return [
                    RouteOptimizationComponent::PICKING_STRATEGY_ALPHANUMERICALLY => __('Alphanumerically'),
                    RouteOptimizationComponent::PICKING_STRATEGY_MOST_INVENTORY => __('Most inventory'),
                    RouteOptimizationComponent::PICKING_STRATEGY_LEAST_INVENTORY => __('Least inventory'),
                ];
            })->readOnly(),
            Str::make('weight_units')->extractUsing(static function (Customer $customer) {
                return $customer::WEIGHT_UNITS;
            })->readOnly(),
            Str::make('endorsements')->extractUsing(static function (Customer $customer) {
                return EasypostCredential::ENDORSEMENT;
            })->readOnly(),
            Str::make('available_rate_cards')->extractUsing(static function (Customer $customer) {
                return RateCard::where3plId($customer->parent_id)->pluck('name', 'id');
            })->readOnly(),
            Str::make('lot_priorities')->extractUsing(static function (Customer $customer) {
                return \App\Enums\LotPriority::translatedValues();
            })->readOnly(),
            HasMany::make('images')->readOnly(),
            HasMany::make('easypost_credentials')->readOnly(),
            HasMany::make('webshipper_credentials')->readOnly(),
            HasMany::make('order_channels')->readOnly(),
            HasMany::make('customer_settings')->readOnly(),
            HasMany::make('printers')->readOnly(),
            HasMany::make('shipping_boxes')->readOnly(),
            HasMany::make('shipping_methods')->type('shipping-methods')->readOnly(),
            HasMany::make('shipping_methods.shipping_carrier')->type('shipping-carriers')->readOnly(),
            HasOne::make('contact_information')->readOnly(),
            HasMany::make('rate_cards')->readOnly(),
            Str::make('primary_rate_card')->extractUsing(static function (Customer $customer) {
                return $customer->primaryRateCard();
            })->readOnly(),
            Str::make('secondary_rate_card')->extractUsing(static function (Customer $customer) {
                return $customer->secondaryRateCard();
            })->readOnly(),
            Map::make('contact_information_data', [])->hidden(),
            Map::make('user_settings_data', [])->hidden(),
            Map::make('customer_settings_data', [])->hidden(),
            Map::make('rate_cards_data', [])->hidden(),
            HasMany::make('children')->type('customers')->readOnly(),
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
            WhereLike::make('name', 'contactInformation.name'),
            WhereNotAssignedToAutomation::make('no-automation','id'),
            Where::make('parent-id','parent_id')
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
