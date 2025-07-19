<?php

namespace App\JsonApi\PublicV1\Orders;

use App\JsonApi\PublicV1\Customers\CustomerSchema;
use App\JsonApi\PublicV1\Server;
use App\Models\Order;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Map;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOne;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Has;
use LaravelJsonApi\Eloquent\Filters\Scope;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereHas;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Filters\WhereNotNull;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Laravel\Pennant\Feature;
use App\Features\VisibleOrderChannelPayload;

class OrderSchema extends Schema
{

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Order::class;

    protected ?array $defaultPagination = [
        'number' => 1,
        'size' => Server::DEFAULT_PAGE_SIZE
    ];

    protected int $maxDepth = 5;

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
            BelongsTo::make('shipping_method')->readOnlyOnUpdate(),
            HasOne::make('shipping_contact_information', 'shippingContactInformation')->type('contact-informations'),
            HasOne::make('billing_contact_information', 'billingContactInformation')->type('contact-informations'),
            HasOne::make('order_channel')->readOnlyOnUpdate(),
            HasOne::make('shipping_box')->readOnlyOnUpdate(),
            Str::make('order_channel_name')->hidden(),
            Str::make('number')->readOnlyOnUpdate(),
            Str::make('status_text')
                ->extractUsing(static function (Order $order) {
                    return $order->getStatusText();
                })
                ->readOnly(),
            Number::make('shipping'),
            Number::make('tax'),
            Number::make('discount'),
            Number::make('total'),
            Boolean::make('ready_to_ship')->readOnly(),
            Boolean::make('ready_to_pick')->readOnly(),
            Boolean::make('is_wholesale'),
            Boolean::make('fraud_hold'),
            Boolean::make('address_hold'),
            Boolean::make('payment_hold'),
            Boolean::make('operator_hold'),
            Boolean::make('allow_partial'),
            DateTime::make('ordered_at'),
            DateTime::make('updated_at'),
            DateTime::make('fulfilled_at'),
            DateTime::make('cancelled_at'),
            DateTime::make('archived_at'),
            DateTime::make('hold_until'),
            DateTime::make('ship_before'),
            DateTime::make('scheduled_delivery'),
            Str::make('gift_note'),
            Str::make('internal_note'),
            Str::make('slip_note'),
            Str::make('external_id'),
            Str::make('packing_note'),
            Str::make('shipping_method_name'),
            Str::make('shipping_method_code'),
            Str::make('tote')
                ->extractUsing(static function (Order $order) {
                    return $order->orderItems
                        ->map(fn($orderItem) => $orderItem->tote()?->barcode)
                        ->whereNotNull()
                        ->unique()
                        ->join(', ');
                })
                ->readOnly(),
            Str::make('tags')
                ->serializeUsing(static function ($value) {
                    return $value->pluck('name')->join(', ');
                }),
            Str::make('order_channel_payload')->hidden(
                    static fn() => !Feature::for('instance')->active(VisibleOrderChannelPayload::class)
                )->readOnly(),
            Str::make('custom_invoice_url')->hidden(),
            HasMany::make('order_items'),
            HasMany::make('shipments')->readOnly(),
            HasMany::make('returns')->readOnly(),
            Map::make('order_item_data', [])->hidden(),
            Map::make('shipping_contact_information_data', [])->hidden(),
            Map::make('billing_contact_information_data', [])->hidden(),
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
            Where::make('number'),
            Where::make('ready_to_ship'),
            Where::make('ready_to_pick'),
            Where::make('external_id'),
            Where::make('fraud_hold'),
            Where::make('address_hold'),
            Where::make('payment_hold'),
            Where::make('operator_hold'),
            Scope::make('tote', 'toteBarcode'),
            Where::make('ordered_at_min', 'ordered_at')->gte(),
            Where::make('ordered_at_max', 'ordered_at')->lte(),
            Where::make('updated_at_min', 'updated_at')->gte(),
            Where::make('updated_at_max', 'updated_at')->lte(),
            Where::make('fulfilled_at_min', 'fulfilled_at')->gte(),
            Where::make('fulfilled_at_max', 'fulfilled_at')->lte(),
            WhereNotNull::make('fulfilled', 'fulfilled_at'),
            WhereNotNull::make('hold', 'hold_until'),
            Where::make('cancelled_at_min', 'cancelled_at')->gte(),
            Where::make('cancelled_at_max', 'cancelled_at')->lte(),
            WhereNotNull::make('cancelled', 'cancelled_at'),
            Where::make('archived_at_min', 'archived_at')->gte(),
            Where::make('archived_at_max', 'archived_at')->lte(),
            WhereNotNull::make('archived', 'archived_at'),
            WhereHas::make($this, 'order_items', 'product'),
            WhereIn::make('customer', 'customer_id')->delimiter(','),
            Has::make($this, 'returns', 'has-returns')
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
