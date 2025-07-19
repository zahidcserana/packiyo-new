<?php

namespace App\Models;

use App\Models\Customer;
use App\Traits\Audits\LocationAudit;
use App\Traits\HasBarcodeTrait;
use App\Traits\HasPrintables;
use App\Traits\SortableForCycleCounts;
use Database\Factories\LocationFactory;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

/**
 * App\Models\Location
 *
 * @property int $id
 * @property int $warehouse_id
 * @property string $name
 * @property int $pickable
 * @property int $pickable_effective
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string|null $barcode
 * @property int $protected
 * @property int $sellable
 * @property int $sellable_effective
 * @property string|null $priority_counting_requested_at
 * @property int|null $location_type_id
 * @property string|null $last_counted_at
 * @property int $disabled_on_picking_app
 * @property int $disabled_on_picking_app_effective
 * @property int $is_receiving
 * @property int|null $bulk_ship_pickable
 * @property int|null $bulk_ship_pickable_effective
 * @property-read string $is_bulk_ship_pickable_label
 * @property-read string $is_disabled_on_picking_app_label
 * @property-read string $is_pickable_label
 * @property-read string $is_sellable_label
 * @property-read Collection|\App\Models\InventoryLog[] $inventoryLogAssociatedObject
 * @property-read int|null $inventory_log_associated_object_count
 * @property-read Collection|\App\Models\LocationProduct[] $locationProducts
 * @property-read int|null $location_products_count
 * @property-read \App\Models\LocationType|null $locationType
 * @property-read Collection|\App\Models\LotItem[] $lotItems
 * @property-read int|null $lot_items_count
 * @property-read Collection|\App\Models\Lot[] $lots
 * @property-read int|null $lots_count
 * @property-read Collection|\App\Models\LotItem[] $placedLotItems
 * @property-read int|null $placed_lot_items_count
 * @property-read Collection|\App\Models\Product[] $products
 * @property-read int|null $products_count
 * @property-read Collection|\App\Models\ToteOrderItem[] $toteOrderItems
 * @property-read int|null $tote_order_items_count
 * @property-read \App\Models\Warehouse $warehouse
 * @method static \Database\Factories\LocationFactory factory(...$parameters)
 * @method static Builder|Location newModelQuery()
 * @method static Builder|Location newQuery()
 * @method static \Illuminate\Database\Query\Builder|Location onlyTrashed()
 * @method static Builder|Location query()
 * @method static Builder|Location whereBarcode($value)
 * @method static Builder|Location whereBulkShipPickable($value)
 * @method static Builder|Location whereBulkShipPickableEffective($value)
 * @method static Builder|Location whereCreatedAt($value)
 * @method static Builder|Location whereDeletedAt($value)
 * @method static Builder|Location whereDisabledOnPickingApp($value)
 * @method static Builder|Location whereDisabledOnPickingAppEffective($value)
 * @method static Builder|Location whereId($value)
 * @method static Builder|Location whereIsReceiving($value)
 * @method static Builder|Location whereLastCountedAt($value)
 * @method static Builder|Location whereLocationTypeId($value)
 * @method static Builder|Location whereName($value)
 * @method static Builder|Location wherePickable($value)
 * @method static Builder|Location wherePickableEffective($value)
 * @method static Builder|Location wherePriorityCountingRequestedAt($value)
 * @method static Builder|Location whereProductCanBeAdded(\App\Models\Product $product, ?\App\Models\Lot $lot = null)
 * @method static Builder|Location whereProtected($value)
 * @method static Builder|Location whereSellable($value)
 * @method static Builder|Location whereSellableEffective($value)
 * @method static Builder|Location whereUpdatedAt($value)
 * @method static Builder|Location whereWarehouseId($value)
 * @method static \Illuminate\Database\Query\Builder|Location withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Location withoutTrashed()
 * @mixin \Eloquent
 */
class Location extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, CascadeSoftDeletes, HasBarcodeTrait, HasPrintables, SortableForCycleCounts;

    use AuditableTrait, LocationAudit {
        LocationAudit::transformAudit insteadof AuditableTrait;
    }

    public const PROTECTED_LOC_NAME_RESHIP = 'Reship';

    protected $cascadeDeletes = [
        'products',
        'cycleCountBatchItems'
    ];

    protected $appends = [
        'is_sellable_label',
        'is_pickable_label',
        'is_bulk_ship_pickable_label',
        'is_disabled_on_picking_app_label'
    ];

    protected $fillable = [
        'warehouse_id',
        'name',
        'pickable',
        'pickable_effective',
        'sellable',
        'sellable_effective',
        'barcode',
        'location_type_id',
        'protected',
        'priority_counting_requested_at',
        'bulk_ship_pickable',
        'bulk_ship_pickable_effective',
        'disabled_on_picking_app',
        'disabled_on_picking_app_effective',
        'is_receiving'
    ];

    protected $attributes = [
        'pickable' => 0,
        'sellable' => 0,
        'disabled_on_picking_app' => 0,
        'bulk_ship_pickable' => 0
    ];

    protected $auditEvents = [
        'created' => 'getCreatedEventAttributes',
        'updated' => 'getUpdatedEventAttributes',
    ];

    protected $auditInclude = [
        'name',
        'pickable',
        'pickable_effective',
        'barcode',
        'protected',
        'priority_counting_requested_at',
        'sellable',
        'sellable_effective',
        'location_type_id',
        'last_counted_at',
        'disabled_on_picking_app',
        'disabled_on_picking_app_effective',
        'is_receiving',
        'bulk_ship_pickable',
        'bulk_ship_pickable_effective',
    ];

    public static $columnBoolean = [
        'pickable',
        'pickable_effective',
        'sellable',
        'sellable_effective',
        'disabled_on_picking_app',
        'disabled_on_picking_app_effective',
        'is_receiving',
        'bulk_ship_pickable',
        'bulk_ship_pickable_effective',
    ];

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

        return [[], $new];
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

        return [$old, $new];
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class)->withTrashed();
    }

    public function customer()
    {
        return $this->hasOneThrough(Customer::class, Warehouse::class, 'id', 'id', 'warehouse_id', 'customer_id');
    }

    public function cycleCountBatchItems()
    {
        return $this->hasMany(CycleCountBatchItem::class);
    }

    public function locationProducts()
    {
        return $this->hasMany(LocationProduct::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class)
            ->using(LocationProduct::class)
            ->withPivot([
                'quantity_on_hand',
                'quantity_reserved_for_picking'
            ])
            ->orderBy('name');
    }

    public function inventoryLogAssociatedObject()
    {
        return $this->morphMany(InventoryLog::class, 'associated_object');
    }

    public function isPickableLabel(): string
    {
        if ($this->pickable === $this->pickable_effective) {
            $label = $this->pickable ? 'Yes' : 'No';
        } else {
            $label = $this->pickable_effective ? 'Yes (Set by Location Type)' : 'No (Set by Location Type)';
        }

        if ($this->pickable_effective && $this->disabled_on_picking_app_effective) {
            $label .= ' (Disabled on picking app)';
        }

        return $label;
    }

    public function isSellableLabel(): string
    {
        if ($this->sellable === $this->sellable_effective) {
            return $this->sellable ? 'Yes' : 'No';
        }

        return $this->sellable_effective ? 'Yes (Set by Location Type)' : 'No (Set by Location Type)';
    }

    public function isBulkShipPickableLabel(): string
    {
        if ($this->bulk_ship_pickable === $this->bulk_ship_pickable_effective) {
            return $this->bulk_ship_pickable ? 'Yes' : 'No';
        }

        return $this->bulk_ship_pickable_effective ? 'Yes (Set by Location Type)' : 'No (Set by Location Type)';
    }

    public function isDisabledOnPickingAppLabel(): string
    {
        if ($this->disabled_on_picking_app === $this->disabled_on_picking_app_effective) {
            return $this->disabled_on_picking_app ? 'Yes' : 'No';
        }

        return $this->disabled_on_picking_app_effective ? 'Yes (Set by Location Type)' : 'No (Set by Location Type)';
    }

    public function getIsSellableLabelAttribute(): string
    {
        $this->attributes['is_sellable_label'] = $this->isSellableLabel();

        return $this->attributes['is_sellable_label'];
    }

    public function getIsPickableLabelAttribute(): string
    {
        $this->attributes['is_pickable_label'] = $this->isPickableLabel();

        return $this->attributes['is_pickable_label'];
    }

    public function getIsBulkShipPickableLabelAttribute(): string
    {
        $this->attributes['is_bulk_ship_pickable_label'] = $this->isBulkShipPickableLabel();

        return $this->attributes['is_bulk_ship_pickable_label'];
    }

    public function getIsDisabledOnPickingAppLabelAttribute(): string
    {
        $this->attributes['is_disabled_on_picking_app_label'] = $this->isDisabledOnPickingAppLabel();

        return $this->attributes['is_disabled_on_picking_app_label'];
    }

    public function toteOrderItems()
    {
        return $this->hasMany(ToteOrderItem::class)->withoutTrashed();
    }

    public function locationType(): BelongsTo
    {
        return $this->belongsTo(LocationType::class);
    }

    public function lotItems()
    {
        return $this->hasMany(LotItem::class, 'location_id', 'id');
    }

    public function placedLotItems()
    {
        return $this->hasMany(LotItem::class)->where('quantity_remaining', '>', 0);
    }

    public function lots()
    {
        return $this->hasManyThrough(
            Lot::class,
            LotItem::class,
            'location_id',
            'id',
            'id',
            'lot_id',
        );
    }

    public function scopeWhereProductCanBeAdded(Builder $query, Product $product, Lot $lot = null)
    {
        $query
            ->where('is_receiving', 1)
            ->orWhere(static function (Builder $query) use ($lot, $product) {
                $query
                    ->when(!$product->lot_tracking, fn (Builder $query) => $query->whereDoesntHave('placedLotItems'))
                    ->when($product->lot_tracking, static function (Builder $query) use ($product, $lot) {
                        $query
                            ->whereDoesntHave('products')
                            ->orWhereHas(
                                'placedLotItems',
                                fn (Builder $query) => $query->where('lot_id', $lot->id ?? null)
                            )
                            // this is required for adding lot information
                            // after product was added to the location beforehand
                            ->orWhere(static function (Builder $query) use ($product) {
                                $query
                                    ->whereHas(
                                        'products',
                                        fn (Builder $query) => $query->where('product_id', $product->id)
                                    )
                                    ->whereDoesntHave('placedLotItems');
                            });
                    });
            });
    }

    public function totalProductsByDate(Carbon $date, Customer $customer = null): int
    {
        $total = 0;

        if (is_null($customer)) {
            $locationProducts = $this->locationProducts;
        } else {
            $locationProducts = $this
                ->locationProducts()
                ->whereHas('product', fn (Builder $builder) => $builder->where('customer_id', $customer->id))
                ->get();
        }

        // TODO: Replace by a reduce() call?
        foreach ($locationProducts as $locationProduct) {
            $total += $locationProduct->quantityByDate($date);
        }

        return $total;
    }
}
