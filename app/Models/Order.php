<?php

namespace App\Models;

use App\Features\LockOrderItemsWhilePicking;
use App\Features\AllowCancelledItemsOnBulkShip;
use App\Enums\Source;
use App\Features\AllowGenericOnBulkShipping;
use App\Features\MultiWarehouse;
use App\Features\PartialOrdersBulkShip;
use App\Features\PendingOrderSlip;
use App\Interfaces\AutomatableOperation;
use App\Interfaces\ItemQuantities;
use App\Traits\HasPrintables;
use App\Traits\HasUniqueIdentifierSuggestionTrait;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Igaster\LaravelCities\Geo;
use Illuminate\Database\{
    Eloquent\Collection,
    Eloquent\Factories\HasFactory,
    Eloquent\Model,
    Eloquent\Relations\BelongsTo,
    Eloquent\Relations\HasOne,
    Eloquent\SoftDeletes};
use Illuminate\Support\{Arr, Carbon};
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Laravel\Pennant\Feature;
use App\Traits\Audits\OrderAudit;
use InvalidArgumentException;


/**
 * App\Models\Order
 *
 * @property int $id
 * @property int $customer_id
 * @property int|null $order_channel_id
 * @property int|null $order_channel
 * @property int|null $shipping_contact_information_id
 * @property int|null $billing_contact_information_id
 * @property int|null $order_status_id
 * @property int|null $shipping_method_id
 * @property string|null $incoterms
 * @property int|null $return_shipping_method_id
 * @property string $number
 * @property Carbon|null $ordered_at
 * @property Carbon|null $fulfilled_at
 * @property Carbon|null $archived_at
 * @property Carbon|null $hold_until
 * @property Carbon|null $ship_before
 * @property string|null $slip_note
 * @property string|null $internal_note
 * @property string|null $packing_note
 * @property int $priority
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string|null $gift_note
 * @property int $fraud_hold
 * @property int $address_hold
 * @property int $payment_hold
 * @property int $operator_hold
 * @property int|null $priority_score
 * @property int $allow_partial
 * @property int $ready_to_ship
 * @property float $subtotal
 * @property float $shipping
 * @property float $tax
 * @property float $total
 * @property int|null $currency_id
 * @property int|null $drop_point_id
 * @property string|null $shipping_lat
 * @property string|null $shipping_lng
 * @property Carbon|null $cancelled_at
 * @property string|null $external_id
 * @property string|null $shipping_method_name
 * @property string|null $order_slip
 * @property float $weight
 * @property string|null $shipping_method_code
 * @property int $quantity_pending_sum
 * @property int $quantity_allocated_sum
 * @property int $quantity_allocated_pickable_sum
 * @property int $quantity_backordered_sum
 * @property int $ready_to_pick
 * @property int $allocation_hold
 * @property string|null $custom_invoice_url
 * @property float $discount
 * @property float $pending_weight
 * @property int|null $shipping_box_id
 * @property int|null $warehouse_id
 * @property float $shipping_discount
 * @property float $shipping_tax
 * @property string|null $delivery_confirmation
 * @property float $packing_length
 * @property float $packing_width
 * @property float $packing_height
 * @property object|null $order_channel_payload
 * @property string|null $batch_key
 * @property string|null $hazmat
 * @property string|null $handling_instructions
 * @property int $saturday_delivery
 * @property bool|null $is_wholesale
 * @property Carbon|null $scheduled_delivery
 * @property Source|null $source
 * @property bool|null $disabled_on_picking_app
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Automation> $actedOnByAutomations
 * @property-read int|null $acted_on_by_automations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Audit> $audits
 * @property-read int|null $audits_count
 * @property-read \App\Models\ContactInformation|null $billingContactInformation
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BulkShipBatch> $bulkShipBatch
 * @property-read int|null $bulk_ship_batch_count
 * @property-read \App\Models\Currency|null $currency
 * @property-read \App\Models\Customer $customer
 * @property-read mixed $age
 * @property-read bool $has_holds
 * @property-read mixed $is_archived
 * @property-read mixed $multiplied_priority
 * @property-read mixed $true_priority
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InventoryLog> $inventoryLogDestinations
 * @property-read int|null $inventory_log_destinations_count
 * @property-read \App\Models\ShippingMethodMapping|null $mappedShippingMethod
 * @property-read \App\Models\OrderChannel|null $orderChannel
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderItem> $orderItems
 * @property-read int|null $order_items_count
 * @property-read \App\Models\OrderLock|null $orderLock
 * @property-read \App\Models\OrderStatus|null $orderStatus
 * @property-read \App\Models\PurchaseOrder|null $purchaseOrder
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReturnItem> $returnItems
 * @property-read int|null $return_items_count
 * @property-read \App\Models\ShippingMethod|null $returnShippingMethod
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Return_> $returns
 * @property-read int|null $returns_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Shipment> $shipments
 * @property-read int|null $shipments_count
 * @property-read \App\Models\ShippingBox|null $shippingBox
 * @property-read \App\Models\ContactInformation|null $shippingContactInformation
 * @property-read \App\Models\ShippingMethod|null $shippingMethod
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tag> $tags
 * @property-read int|null $tags_count
 * @property-read \App\Models\Tote|null $tote
 * @property-read \App\Models\Warehouse|null $warehouse
 * @method static \Database\Factories\OrderFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order notPickedOrders()
 * @method static \Illuminate\Database\Eloquent\Builder|Order onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder|Order toteBarcode($toteBarcode)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereAddressHold($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereAllocationHold($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereAllowPartial($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereArchivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereBatchKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereBillingContactInformationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCustomInvoiceUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDeliveryConfirmation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDisabledOnPickingApp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDropPointId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereFraudHold($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereFulfilledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereGiftNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereHandlingInstructions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereHazmat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereHoldUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereIncoterms($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereInternalNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereIsWholesale($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOperatorHold($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderChannelPayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderSlip($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePackingHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePackingLength($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePackingNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePackingWidth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePaymentHold($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePendingWeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePriorityScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereQuantityAllocatedPickableSum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereQuantityAllocatedSum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereQuantityBackorderedSum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereQuantityPendingSum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereReadyToPick($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereReadyToShip($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereReturnShippingMethodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereSaturdayDelivery($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereScheduledDelivery($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereShipBefore($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereShipping($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereShippingBoxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereShippingContactInformationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereShippingDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereShippingLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereShippingLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereShippingMethodCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereShippingMethodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereShippingMethodName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereShippingTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereSlipNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereWarehouseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereWeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Order withoutTrashed()
 * @mixin \Eloquent
 */

class Order extends Model implements AuditableInterface, AutomatableOperation, ItemQuantities
{
    use HasFactory, SoftDeletes, CascadeSoftDeletes, HasPrintables, HasUniqueIdentifierSuggestionTrait;

    use AuditableTrait, OrderAudit {
        OrderAudit::transformAudit insteadof AuditableTrait;
    }

    protected $cascadeDeletes = [
        'orderItems',
        'shipments',
        'shippingContactInformation',
        'billingContactInformation',
        'returns'
    ];

    protected $fillable = [
        'customer_id',
        'order_channel_id',
        'external_id',
        'shipping_contact_information_id',
        'billing_contact_information_id',
        'order_status_id',
        'number',
        'ordered_at',
        'hold_until',
        'ship_before',
        'slip_note',
        'packing_note',
        'internal_note',
        'priority',
        'priority_score',
        'gift_note',
        'fraud_hold',
        'allocation_hold',
        'address_hold',
        'payment_hold',
        'operator_hold',
        'allow_partial',
        'disabled_on_picking_app',
        'ready_to_ship',
        'ready_to_pick',
        'tax',
        'discount',
        'shipping',
        'shipping_method_id',
        'incoterms',
        'return_shipping_method_id',
        'shipping_method_name',
        'shipping_method_code',
        'order_slip',
        'drop_point_id',
        'currency_id',
        'quantity_pending_sum',
        'quantity_allocated_sum',
        'quantity_allocated_pickable_sum',
        'custom_invoice_url',
        'batch_key',
        'archived_at',
        'shipping_box_id',
        'warehouse_id',
        'shipping_discount',
        'shipping_tax',
        'delivery_confirmation',
        'order_channel_payload',
        'handling_instructions',
        'saturday_delivery',
        'hazmat',
        'is_wholesale',
        'scheduled_delivery',
        'is_wholesale',
        'source'
    ];

    protected $dates = [
        'ordered_at',
        'fulfilled_at',
        'cancelled_at',
        'hold_until',
        'ship_before',
        'archived_at',
        'scheduled_delivery'
    ];

    protected $attributes = [
        'is_wholesale' => false
    ];

    protected $casts = [
        'order_channel_payload' => 'object',
        'is_wholesale' => 'bool',
        'disabled_on_picking_app' => 'bool',
        'source'=> Source::class
    ];

    public const STATUS_FULFILLED = 'Fulfilled';
    public const STATUS_CANCELLED = 'Cancelled';
    public const STATUS_PENDING = 'Pending';
    public const ORDER_STATUSES = [
        'pending' => self::STATUS_PENDING,
        'fulfilled' => self::STATUS_FULFILLED,
        'cancelled' => self::STATUS_CANCELLED
    ];

    public const ORDER_PREFIX = 'MO-';
    public const TRANSFER_ORDER_PREFIX = 'TO-';
    public static $uniqueIdentifierColumn = 'number';
    public static $uniqueIdentifierReferenceColumn = 'customer_id';
    public const DELIVERY_CONFIRMATION_SIGNATURE = 'yes';
    public const DELIVERY_CONFIRMATION_ADULT_SIGNATURE = 'adult';
    public const DELIVERY_CONFIRMATION_NO_SIGNATURE = 'no';

    public const ORDER_TYPE_REGULAR = 'regular';
    public const ORDER_TYPE_TRANSFER = 'transfer';

    public const ORDER_TYPES = [
        self::ORDER_TYPE_REGULAR => 'Regular',
        self::ORDER_TYPE_TRANSFER => 'Transfer'
    ];

    public const INCOTERMS_DDP = 'DDP';
    public const INCOTERMS_DDU = 'DDU';

    /**
     * Audit configs
     */
    protected $auditStrict = true;

    protected $auditEvents = [
        'created' => 'getCreatedEventAttributes',
        'updated' => 'getUpdatedEventAttributes',
    ];

    protected $auditInclude = [
        'hold_until',
        'ship_before',
        'scheduled_delivery',
        'notes',
        'gift_note',
        'internal_note',
        'packing_note',
        'slip_note',
        'weight',
        'shipping_priority_score',
        'priority',
        'order_status_id',
        'shipping_method_id',
        'incoterms',
        'fulfilled_at',
        'operator_hold',
        'payment_hold',
        'allocation_hold',
        'address_hold',
        'fraud_hold',
        'allow_partial',
        'disabled_on_picking_app',
        'subtotal',
        'shipping',
        'tax',
        'total',
        'warehouse_id',
        'shipping_discount',
        'shipping_tax',
        'delivery_confirmation',
        'ready_to_ship',
        'ready_to_pick',
    ];

    public static $columnBoolean = [
        'priority',
        'operator_hold',
        'payment_hold',
        'address_hold',
        'fraud_hold',
        'allocation_hold',
        'allow_partial',
        'ready_to_ship',
        'ready_to_pick',
    ];

    protected $printables = ['order_slip'];
    protected $printableUrls = ['order.orderSlip'];

    public function customer()
    {
        return $this->belongsTo(Customer::class)->with('contactInformation')->withTrashed();
    }

    public function orderChannel()
    {
        return $this->belongsTo(OrderChannel::class);
    }

    public function orderStatus()
    {
        return $this->belongsTo(OrderStatus::class)->withTrashed();
    }

    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class)->withTrashed();
    }

    public function returnShippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class, 'return_shipping_method_id', 'id')->withTrashed();
    }

    public function mappedShippingMethod()
    {
        return $this->belongsTo(ShippingMethodMapping::class, 'shipping_method_name', 'shipping_method_name')
            ->where('customer_id', $this->customer_id);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function returnItems()
    {
        return $this->hasManyThrough(
            ReturnItem::class,
            Return_::class,
            'order_id',
            'return_id',
            'id',
            'id'
        );
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    public function shippingContactInformation()
    {
        return $this->belongsTo(ContactInformation::class, 'shipping_contact_information_id', 'id')->withTrashed();
    }

    public function billingContactInformation()
    {
        return $this->belongsTo(ContactInformation::class, 'billing_contact_information_id', 'id')->withTrashed();
    }

    public function returns()
    {
        return $this->hasMany(Return_::class);
    }

    public function inventoryLogDestinations()
    {
        return $this->morphMany(InventoryLog::class, 'destination')->withTrashed();
    }

    public function orderLock()
    {
        return $this->hasOne(OrderLock::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function bulkShipBatch()
    {
        return $this->belongsToMany(BulkShipBatch::class);
    }

    public function shippingBox()
    {
        return $this->belongsTo(ShippingBox::class);
    }

    public function actedOnByAutomations(): BelongsToMany
    {
        return $this->belongsToMany(
            Automation::class,
            'automation_acted_on_operation',
            'operation_id',
            'automation_id'
        )
        ->wherePivot('operation_type', static::class);
    }

    public function items(): Collection
    {
        return $this->orderItems;
    }

    public function orderItemsForOrderSlip()
    {
        $quantityColumn = 'quantity';

        if (Feature::for('instance')->active(PendingOrderSlip::class)) {
            $quantityColumn = 'quantity_pending';
        }

        return $this->orderItems
            ->whereNull('cancelled_at')
            ->where($quantityColumn, '>', 0);
    }

    public function getMultipliedPriorityAttribute()
    {
        return $this->priority * 10;
    }

    public function getAgeAttribute()
    {
        if (!empty($this->created_at) && !empty($this->ordered_at)) {
            return $this->ordered_at->diffInDays($this->created_at);
        }

        return 0;
    }

    public function getTruePriorityAttribute()
    {
        return $this->MultipliedPriority + $this->Age;
    }

    public function getIsArchivedAttribute()
    {
        return !is_null($this->archived_at);
    }

    public function unshippedOrders()
    {
        return $this->doesntHave('shipments')->get();
    }

    public function recalculateWeight()
    {
        $this->weight = 0;
        $this->pending_weight = 0;

        foreach ($this->orderItems as $orderItem) {
            $this->weight += $orderItem->weight * $orderItem->quantity;
            $this->pending_weight += $orderItem->weight * $orderItem->quantity_pending;
        }
    }

    // TODO: we should store this on the order table and recalculate when order was updated
    public function getTotal()
    {
        $total = 0;

        foreach ($this->orderItems as $orderItem) {
            $total += $orderItem->price * $orderItem->quantity;
        }

        return $total;
    }

    public function tote()
    {
        return $this->hasOne(Tote::class)->withTrashed();
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function pickingBatchOfType($type = PickingBatch::TYPE_SO)
    {
        return PickingBatch::where('type', $type)
            ->whereNull('deleted_at')
            ->whereJsonContains('order_ids', $this->id)->first();
    }

    public function getMapCoordinates()
    {
        $city = Arr::get($this->shippingContactInformation, 'city', '');
        $countryCode = Arr::get($this->shippingContactInformation, 'country.iso_3166_2', '');

        $coordinates = Geo::where('name', $city)
            ->where('country', $countryCode)
            ->first();

        if ($coordinates) {
            $this->shipping_lat = $coordinates->lat;
            $this->shipping_lng = $coordinates->long;

            $this->save();
        }
    }

    public function scopeNotPickedOrders($query)
    {
        $user = auth()->user();

        $notCompletedTaskIds = Task::where('user_id', $user->id)->where('taskable_type', PickingBatch::class)->where('completed_at', null)->pluck('taskable_id')->toArray();

        $orderLockIds = OrderLock::where('user_id', '=', $user->id)->where('lock_type', OrderLock::LOCK_TYPE_PICKING)->pluck('id')->toArray();

        $orders = Order::with(['orderItems.pickingBatchItems.pickingBatch', 'orderItems.placedToteOrderItems'])
            ->where('ready_to_pick', 1)
            ->where(function ($query) {
                $query->whereNull('disabled_on_picking_app')
                    ->orWhere('disabled_on_picking_app', 0);
            })
            ->whereDoesntHave('orderLock')
            ->orWhereHas('orderLock', function ($query) use ($orderLockIds) {
                $query->whereIn('id', $orderLockIds);
            })
            ->get();

        $ordersIds = [];

        foreach ($orders as $order) {
            $pickingBatch = null;
            $orderItems = $order->orderItems;

            foreach ($orderItems as $orderItem) {
                $pickingBatchItems = $orderItem->pickingBatchItems;

                foreach ($pickingBatchItems as $pickingBatchItem) {
                    $pickingBatch = $pickingBatchItem->pickingBatch;
                }
            }

            if ($pickingBatch) {
                if ($pickingBatch->type === 'so' && in_array($pickingBatch->id, $notCompletedTaskIds)) {
                    $ordersIds[] = $order->id;
                }
            } else {
                $totalAllocated = $orderItems->sum('quantity_allocated');
                $totalPickedQuantity = $orderItems->sum(function ($orderItem) {
                    return $orderItem->placedToteOrderItems->sum('quantity');
                });

                if ($totalAllocated != $totalPickedQuantity) {
                    $ordersIds[] = $order->id;
                }
            }
        }

        return $query->whereIntegerInRaw('id', $ordersIds);
    }

    public function scopeToteBarcode($query, $toteBarcode)
    {
        return $query
            ->whereHas('orderItems.placedToteOrderItems.tote', fn($query) => $query->where('barcode', $toteBarcode));
    }

    /**
     * @return string
     */
    public function getStatusText(): string
    {
        if ($this->fulfilled_at) {
            return self::STATUS_FULFILLED;
        }

        if ($this->cancelled_at) {
            return self::STATUS_CANCELLED;
        }

        return $this->orderStatus->name ?? self::STATUS_PENDING;
    }

    /**
     * Get the old/new attributes of a created event.
     *
     * @return array
     */
    public function getCreatedEventAttributes(): array
    {
        $new = [];

        foreach ($this->attributes as $attribute => $value) {
            if ($this->isAttributeAuditable($attribute)) {
                $new[$attribute] = $value;
            }
        }

        return [
            [],
            $new,
        ];
    }

    /**
     * Get the old/new attributes of an updated event.
     *
     * @return array
     */
    protected function getUpdatedEventAttributes(): array
    {
        $old = [];
        $new = [];

        foreach ($this->getDirty() as $attribute => $value) {
            if ($this->isAttributeAuditable($attribute)) {
                $old[$attribute] = Arr::get($this->original, $attribute);
                $new[$attribute] = Arr::get($this->attributes, $attribute);
            }
        }

        return [
            $old,
            $new,
        ];
    }

    /**
     * @param Shipment $shipment
     * @return void
     */
    public function logSingleOrderShipCustomEvent(Shipment $shipment)
    {
        $shipmentTrackings = '';

        if (!is_null($shipment->shipmentTrackings)) {
            foreach($shipment->shipmentTrackings as $tracking) {
                $shipmentTrackings .= $tracking->tracking_url . ', ' . $tracking->tracking_number;
            }
        }

        $message = __('Order was shipped using :shippingMethod :shipmentTrackings', [
                'shippingMethod' => !is_null($shipment->shippingMethod) ? $shipment->shippingMethod->carrierNameAndName : 'Generic',
                'shipmentTrackings' => $shipmentTrackings != '' ? ' - ' . $shipmentTrackings : ''
            ]);

        $this::auditCustomEvent($this, 'shipped', $message);
    }


    /**
     * @return boolean
     */
    public function isEmptyOrderItemQuantityShipped(): bool
    {
        return $this
            ->orderItems
            ->filter(function ($item) {
                return $item->quantity_shipped > 0;
            })
            ->isEmpty();
    }

    public function getHasHoldsAttribute(): bool
    {
        return $this->address_hold || $this->fraud_hold || $this->payment_hold || $this->operator_hold;
    }

    /**
     * @return array
     */
    public function notReadyToShipExplanation(): array
    {
        $reasons = [];

        if ($this->ready_to_ship) {
            return $reasons;
        }

        if ($this->address_hold) {
            $reasons[] = __('The order has an address hold added');
        }

        if ($this->fraud_hold) {
            $reasons[] = __('The order has a fraud hold added');
        }

        if ($this->payment_hold) {
            $reasons[] = __('The order has a payment hold added');
        }

        if ($this->operator_hold) {
            $reasons[] = __('The order has an operator hold added');
        }

        if (!is_null($this->hold_until) && $this->hold_until > Carbon::now()) {
            $reasons[] = __('The hold until date was not met');
        }

        if ($this->allocation_hold) {
            $reasons[] = __('The order has an allocation hold added');
        } else {
            foreach ($this->orderItems as $orderItem) {
                if (!$orderItem->product_id && $orderItem->quantity_pending > 0) {
                    $backorderedItems[] = $orderItem->sku;
                } elseif ($orderItem->quantity_backordered > 0) {
                    $backorderedItems[] = $orderItem->sku;
                } elseif ($orderItem->quantity_pending > $orderItem->quantity_allocated + $orderItem->quantity_backordered) {
                    $pendingItems[] = $orderItem->sku;
                }
            }

            if (!empty($backorderedItems)) {
                $reasons[] = __('Order items with the following SKU are backordered: :skusNotAllocated', ['skusNotAllocated' => implode(', ', $backorderedItems)]);
            }

            if (!empty($pendingItems)) {
                $reasons[] = __('Order items with the following SKU are reprocessing: :skusNotAllocated', ['skusNotAllocated' => implode(', ', $pendingItems)]);
            }
        }

        return $reasons;
    }

    /**
     * @return string|null
     */
    public function notReadyToPickExplanation(): ?string
    {
        $quantitySum = $this->allow_partial ? $this->quantity_allocated_sum : $this->quantity_pending_sum;
        $pendingItems = [];

        if ($this->quantity_allocated_pickable_sum !== $quantitySum) {
            foreach ($this->orderItems as $orderItem) {
                $quantityToCheck = $this->allow_partial ? $orderItem->quantity_allocated : $orderItem->quantity_pending;

                if ($orderItem->quantity_allocated_pickable < $quantityToCheck && (!$orderItem->product || !$orderItem->product->isKit())) {
                    $pendingItems[] = $orderItem->sku;
                }
            }

            if (empty($pendingItems)) {
                return __('Order is being reprocessed');
            } else {
                return __('Order items with the following SKU were not allocated from pickable locations: :skusNotAllocated', ['skusNotAllocated' => implode(', ', $pendingItems)]);
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isLockedForEditing(): bool
    {
        if (Feature::for('instance')->inactive(LockOrderItemsWhilePicking::class)) {
            return false;
        }

        $this->loadMissing([
            'orderItems.placedToteOrderItems',
            'orderLock'
        ]);

        return $this->orderLock || $this->orderItems->pluck('placedToteOrderItem')->count() > 0;
    }

    /**
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return HasOne
     */
    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class, 'order_id', 'id');
    }

    /**
     * @return bool
     */
    public function isTransferOrder(): bool
    {
        return $this->purchaseOrder()->exists();
    }

    /**
     * @return string
     */
    public function orderType(): string
    {
        return $this->isTransferOrder() ? self::ORDER_TYPE_TRANSFER : self::ORDER_TYPE_REGULAR;
    }

    public function regenerateBatchKey(): static
    {
        $oldBatchKey = $this->batch_key;

        $this->batch_key = null;

        if ($this->is_wholesale) {
            return $this;
        }

        if (Feature::for('instance')->active(AllowGenericOnBulkShipping::class)) {
            $shippingMethodCheck = true;
        } else {
            // check if order has shipping method assigned or it's mapped to cheapest
            $shippingMethodCheck = $this->shipping_method_id || ($this->mappedShippingMethod && $this->mappedShippingMethod->type);
        }

        if (
            $this->ready_to_ship == 1 &&
            $this->quantity_pending_sum > 0 &&
            $shippingMethodCheck &&
            !$this->fulfilled_at &&
            !$this->cancelled_at
        ) {
            $this->loadMissing([
                'orderItems.placedToteOrderItems',
                'orderItems.pickingBatchItems',
                'orderItems.product',
            ]);

            $itemsThatMustBeChecked = $this->orderItems
                ->when(
                    $this->allow_partial && Feature::for('instance')->active(PartialOrdersBulkShip::class),
                    fn (Collection $orderItems) => $orderItems->filter(
                        fn(OrderItem $orderItem) => $orderItem->quantity_allocated >= 1
                    )
                );

            $filteredCount = $itemsThatMustBeChecked
                ->filter(function ($orderItem) {
                    $defaultChecks = $orderItem->product &&
                        $orderItem->placedToteOrderItems->count() === 0 &&
                        $orderItem->pickingBatchItems->count() === 0 &&
                        $orderItem->product->has_serial_number === 0 &&
                        $orderItem->quantity_allocated > 0;

                    if (Feature::for('instance')->active(AllowCancelledItemsOnBulkShip::class)) {
                        return $defaultChecks;
                    }

                    return $defaultChecks &&
                        $orderItem->cancelled_at === null;
                })->count();

            if ($itemsThatMustBeChecked->count() === $filteredCount) {
                if (Feature::for('instance')->active(MultiWarehouse::class)) {
                    $batchKey = $itemsThatMustBeChecked
                        ->sortBy('product_id')
                        ->map(fn($orderItem) => "$orderItem->product_id:$orderItem->quantity_allocated:" . $orderItem->order->warehouse_id)
                        ->toArray();
                } else {
                    $batchKey = $itemsThatMustBeChecked
                        ->sortBy('product_id')
                        ->map(fn($orderItem) => "$orderItem->product_id:$orderItem->quantity_allocated")
                        ->toArray();
                }

                $this->batch_key = implode(',', $batchKey);
            }
        }

        if ($oldBatchKey !== $this->batch_key && $this->bulkShipBatch->count() > 0) {
            $this->bulkShipBatch()->detach($this->id);
        }

        return $this;
    }

    public function canBeReshipped(): bool
    {
        $this->load(['orderItems']);

        return $this->orderItems->filter(function ($item) {
            return $item->quantity_reshippable > 0;
        })->isNotEmpty();
    }

    /**
     * @return ShippingBox|null
     */
    public function getDefaultShippingBox(): ShippingBox|null
    {
        if ($this->shippingBox()->exists()) {
            return $this->shippingBox;
        }

        $customer = $this->customer;
        $availableShippingBoxes = $customer->availableShippingBoxes();

        $defaultShippingBoxId = customer_settings($customer->id, CustomerSetting::CUSTOMER_SETTING_SHIPPING_BOX_ID);

        if (!$defaultShippingBoxId && $customer->is3plChild()) {
            $defaultShippingBoxId = customer_settings($customer->parent_id, CustomerSetting::CUSTOMER_SETTING_SHIPPING_BOX_ID);
        }

        return $availableShippingBoxes->find($defaultShippingBoxId) ?? $availableShippingBoxes->first();
    }

    public function getShippingCustomer(): Customer
    {
        return $this->customer->is3plChild() ? $this->customer->parent : $this->customer;
    }

    public function getMappedShippingMethodIdOrType(): int|string|null
    {
        return $this->shipping_method_id ??
            $this->mappedShippingMethod->type ??
            null;
    }

    public function getSource(): ?Source
    {
        return $this->source;
    }

    public function setSource($value): void
    {
        if ($value == null) {
            $this->source = $value;
        } elseif (in_array($value, Source::cases())) {
            $this->source = $value;
        } else {
            throw new InvalidArgumentException("Invalid source $value");
        }
    }

    public function hasMultipleShipments(): bool
    {
        return $this->shipments->count() > 1;
    }
}
