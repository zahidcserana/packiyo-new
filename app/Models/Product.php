<?php

namespace App\Models;

use App\Enums\LotPriority;
use App\Components\RouteOptimizationComponent;
use App\Features\MultiWarehouse;
use App\Traits\{HasBarcodeTrait, HasPrintables, SortableForCycleCounts, Audits\ProductAudit};
use Database\Factories\ProductFactory;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\{Builder,
    Collection,
    Factories\HasFactory,
    Relations\BelongsToMany,
    Relations\HasMany,
    Model,
    Relations\HasOne,
    SoftDeletes};
use Illuminate\Support\Arr;
use Laravel\Pennant\Feature;
use Webpatser\Countries\Countries;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable as AuditableTrait;

/**
 * App\Models\Product
 *
 * @property int $id
 * @property int $customer_id
 * @property string $sku
 * @property string $name
 * @property string $price
 * @property string|null $notes
 * @property int $quantity_on_hand
 * @property int $quantity_reserved
 * @property int $quantity_pickable
 * @property int $quantity_allocated
 * @property int $quantity_inbound
 * @property int $quantity_allocated_pickable
 * @property int $quantity_available
 * @property int $quantity_to_replenish
 * @property int $quantity_non_sellable
 * @property string|null $value
 * @property string|null $customs_price
 * @property string|null $customs_description
 * @property string|null $hs_code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int $quantity_backordered
 * @property float|null $weight
 * @property float|null $height
 * @property float|null $length
 * @property float|null $width
 * @property string|null $barcode
 * @property int|null $country_of_origin
 * @property string|null $priority_counting_requested_at
 * @property int $has_serial_number
 * @property int $reorder_threshold
 * @property int $quantity_reorder
 * @property string|null $last_counted_at
 * @property int|null $lot_tracking
 * @property string|null $lot_priority
 * @property int $inventory_sync
 * @property int|null $product_profile_id
 * @property string $cost
 * @property int $lot_without_expiration
 * @property string|null $hazmat
 * @property-read Collection|\App\Models\Audit[] $audits
 * @property-read int|null $audits_count
 * @property-read Collection|\App\Models\KitItem[] $components
 * @property-read int|null $components_count
 * @property-read Countries|null $country
 * @property-read \App\Models\Customer $customer
 * @property-read Collection|\App\Models\Image[] $images
 * @property-read int|null $images_count
 * @property-read Collection|\App\Models\InventoryLog[] $inventoryLogs
 * @property-read int|null $inventory_logs_count
 * @property-read Collection|Product[] $kitItems
 * @property-read int|null $kit_items_count
 * @property-read Collection|Product[] $kitParents
 * @property-read int|null $kit_parents_count
 * @property-read Collection|\App\Models\KitItem[] $kits
 * @property-read int|null $kits_count
 * @property-read Collection|\App\Models\LocationProduct[] $locationProducts
 * @property-read int|null $location_products_count
 * @property-read Collection|\App\Models\Location[] $locations
 * @property-read int|null $locations_count
 * @property-read Collection|\App\Models\LotItem[] $lotItems
 * @property-read int|null $lot_items_count
 * @property-read Collection|\App\Models\Lot[] $lots
 * @property-read int|null $lots_count
 * @property-read Collection|\App\Models\OrderItem[] $orderItem
 * @property-read Collection|\App\Models\ProductBarcode[] $productBarcodes
 * @property-read int|null $order_item_count
 * @property-read Collection|\App\Models\LotItem[] $placedLotItems
 * @property-read int|null $placed_lot_items_count
 * @property-read Collection|\App\Models\Image[] $productImages
 * @property-read int|null $product_images_count
 * @property-read Collection|\App\Models\PurchaseOrderItem[] $purchaseOrderLine
 * @property-read int|null $purchase_order_line_count
 * @property-read Collection|ShipmentItem[] $shipmentItem
 * @property-read int|null $shipment_item_count
 * @property-read Collection|Supplier[] $suppliers
 * @property-read int|null $suppliers_count
 * @property-read Collection|Tag[] $tags
 * @property-read int|null $tags_count
 * @method static ProductFactory factory(...$parameters)
 * @method static Builder|Product newModelQuery()
 * @method static Builder|Product newQuery()
 * @method static \Illuminate\Database\Query\Builder|Product onlyTrashed()
 * @method static Builder|Product query()
 * @method static Builder|Product whereBarcode($value)
 * @method static Builder|Product whereCost($value)
 * @method static Builder|Product whereCountryOfOrigin($value)
 * @method static Builder|Product whereCreatedAt($value)
 * @method static Builder|Product whereCustomerId($value)
 * @method static Builder|Product whereCustomsDescription($value)
 * @method static Builder|Product whereCustomsPrice($value)
 * @method static Builder|Product whereDeletedAt($value)
 * @method static Builder|Product whereHasSerialNumber($value)
 * @method static Builder|Product whereHazmat($value)
 * @method static Builder|Product whereHeight($value)
 * @method static Builder|Product whereHsCode($value)
 * @method static Builder|Product whereId($value)
 * @method static Builder|Product whereInventorySync($value)
 * @method static Builder|Product whereIsKit($value)
 * @method static Builder|Product whereKitType($value)
 * @method static Builder|Product whereLastCountedAt($value)
 * @method static Builder|Product whereLength($value)
 * @method static Builder|Product whereLotPriority($value)
 * @method static Builder|Product whereLotTracking($value)
 * @method static Builder|Product whereLotWithoutExpiration($value)
 * @method static Builder|Product whereName($value)
 * @method static Builder|Product whereNotes($value)
 * @method static Builder|Product wherePrice($value)
 * @method static Builder|Product wherePriorityCountingRequestedAt($value)
 * @method static Builder|Product whereProductProfileId($value)
 * @method static Builder|Product whereQuantityAllocated($value)
 * @method static Builder|Product whereQuantityAllocatedPickable($value)
 * @method static Builder|Product whereQuantityAvailable($value)
 * @method static Builder|Product whereQuantityBackordered($value)
 * @method static Builder|Product whereQuantityOnHand($value)
 * @method static Builder|Product whereQuantityPickable($value)
 * @method static Builder|Product whereQuantityReorder($value)
 * @method static Builder|Product whereQuantityReserved($value)
 * @method static Builder|Product whereQuantityToReplenish($value)
 * @method static Builder|Product whereReorderThreshold($value)
 * @method static Builder|Product whereSku($value)
 * @method static Builder|Product whereUpdatedAt($value)
 * @method static Builder|Product whereValue($value)
 * @method static Builder|Product whereWeight($value)
 * @method static Builder|Product whereWidth($value)
 * @method static \Illuminate\Database\Query\Builder|Product withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Product withoutTrashed()
 * @mixin \Eloquent
 * @property string|null $type
 * @method static Builder|Product whereType($value)
 */

class Product extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, HasBarcodeTrait, AuditableTrait, HasPrintables, CascadeSoftDeletes, SortableForCycleCounts;

    use AuditableTrait, ProductAudit {
        ProductAudit::transformAudit insteadof AuditableTrait;
    }

    protected $cascadeDeletes = [
        'cycleCountBatchItems'
    ];

    protected $fillable = [
        'sku',
        'name',
        'price',
        'cost',
        'notes',
        'customer_id',
        'quantity_on_hand',
        'quantity_pickable',
        'quantity_allocated',
        'quantity_allocated_pickable',
        'quantity_available',
        'quantity_backordered',
        'quantity_to_replenish',
        'quantity_non_sellable',
        'quantity_inbound',
        'height',
        'weight',
        'length',
        'width',
        'height',
        'barcode',
        'hs_code',
        'value',
        'customs_price',
        'customs_description',
        'country_of_origin',
        'priority_counting_requested_at',
        'has_serial_number',
        'reorder_threshold',
        'quantity_reorder',
        'quantity_reserved',
        'lot_tracking',
        'lot_priority',
        'inventory_sync',
        'hazmat',
        'type',
        'lot_without_expiration'
    ];

    protected $attributes = [
        'price' => 0,
        'hs_code' => '',
        'notes' => '',
        'customs_price' => 0,
        'quantity_on_hand' => 0,
        'quantity_pickable' => 0,
        'quantity_allocated' => 0,
        'quantity_allocated_pickable' => 0,
        'quantity_available' => 0,
        'quantity_backordered' => 0,
        'quantity_to_replenish' => 0,
        'quantity_non_sellable' => 0,
        'quantity_inbound' => 0,
        'weight' => 0,
        'length' => 0,
        'width' => 0,
        'height' => 0,
        'inventory_sync' => 1,
        'type' => self::PRODUCT_TYPE_REGULAR
    ];

    public const PRODUCT_TYPE_REGULAR = 'regular';
    public const PRODUCT_TYPE_STATIC_KIT = 'static_kit';
    public const PRODUCT_TYPE_DYNAMIC_KIT = 'dynamic_kit';
    public const PRODUCT_TYPE_VIRTUAL = 'virtual';

    public const PRODUCT_TYPES = [
        self::PRODUCT_TYPE_REGULAR => 'Regular',
//        self::PRODUCT_TYPE_DYNAMIC_KIT => 'Dynamic Kit',
        self::PRODUCT_TYPE_STATIC_KIT => 'Static Kit',
        self::PRODUCT_TYPE_VIRTUAL => 'Virtual'
    ];

    public const HAZMAT_OPTIONS = [
        'PRIMARY_CONTAINED' => 'Primary Contained',
        'PRIMARY_PACKED' => 'Primary Packed',
        'PRIMARY' => 'Primary',
        'SECONDARY_CONTAINED' => 'Secondary Contained',
        'SECONDARY_PACKED' => 'Secondary Packed',
        'SECONDARY' => 'Secondary',
        'ORMD' => 'Other Regulated Materials—Domestic',
        'LITHIUM' => 'Lithium',
        'LIMITED_QUANTITY' => 'Limited Quantity Ground Package',
        'AIR_ELIGIBLE_ETHANOL' => 'Air Eligible Ethanol Package',
        'CLASS_1' => 'Class 1 - Toy Propellant/Safety Fuse Package',
        'CLASS_3' => 'Class 3 - Package',
        'CLASS_7' => 'Class 7 - Radioactive Materials Package',
        'CLASS_8_CORROSIVE' => 'Class 8 - Corrosive Materials Package',
        'CLASS_8_WET_BATTERY' => 'Class 8 - Nonspillable Wet Battery Package',
        'CLASS_9_NEW_LITHIUM_INDIVIDUAL' => 'Class 9 - Lithium Battery Marked - Ground Only Package',
        'CLASS_9_USED_LITHIUM' => 'Class 9 - Lithium Battery - Returns Package',
        'CLASS_9_NEW_LITHIUM_DEVICE' => 'Class 9 - Lithium batteries, marked package',
        'CLASS_9_DRY_ICE' => 'Class 9 - Dry Ice Package',
        'CLASS_9_UNMARKED_LITHIUM' => 'Class 9 - Lithium batteries, unmarked package',
        'CLASS_9_MAGNETIZED' => 'Class 9 - Magnetized Materials Package',
        'DIVISION_4_1' => 'Division 4.1 - Mailable flammable solids and Safety Matches Package',
        'DIVISION_5_1' => 'Division 5.1 - Oxidizers Package',
        'DIVISION_5_2' => 'Division 5.2 - Organic Peroxides Package',
        'DIVISION_6_1' => 'Division 6.1 - Toxic Materials Package (with an LD50 of 50 mg/kg or less)',
        'DIVISION_6_2' => 'Division 6.2',
        'EXCEPTED_QUANTITY_PROVISION' => 'Excepted Quantity Provision Package',
        'GROUND_ONLY' => 'Ground Only',
        'ID8000' => 'ID8000 Consumer Commodity Package',
        'LIGHTERS' => 'Lighters Package',
        'SMALL_QUANTITY_PROVISION' => 'Small Quantity Provision Package'
    ];

    protected $auditEvents = [
        'created' => 'getCreatedEventAttributes',
        'updated' => 'getUpdatedEventAttributes',
    ];

    protected $auditInclude = [
        'sku',
        'name',
        'price',
        'notes',
        'value',
        'customs_price',
        'customs_description',
        'hs_code',
        'weight',
        'height',
        'length',
        'width',
        'barcode',
        'country_of_origin',
        'priority_counting_requested_at',
        'has_serial_number',
        'reorder_threshold',
        'last_counted_at',
        'lot_tracking',
        'lot_without_expiration',
        'lot_priority',
        'inventory_sync',
        'quantity_inbound',
        'type',
        'cost',
    ];

    public static $columnBoolean = [
        'priority_counting_requested_at',
        'has_serial_number',
        'lot_tracking',
    ];

    public static $columnDecimal = [
        'price',
        'customs_price',
        'cost',
        'value',
    ];

    public static $columnDouble = [
        'weight',
        'height',
        'length',
        'width',
    ];

    protected $casts = [
        'weight' => 'double',
        'height' => 'double',
        'length' => 'double',
        'width' => 'double'
    ];

    public function purchaseOrderLine()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function purchaseOrderLineActive()
    {
        return $this->purchaseOrderLine()
            ->leftJoin('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->whereNull('purchase_orders.closed_at');
    }

    public function shipmentItem()
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function orderItem()
    {
        return $this->hasMany(OrderItem::class);
    }


    public function cycleCountBatchItems()
    {
        return $this->hasMany(CycleCountBatchItem::class);
    }

    public function locationProducts()
    {
        return $this->hasMany(LocationProduct::class);
    }

    public function productBarcodes()
    {
        return $this->hasMany(ProductBarcode::class);
    }

    public function locations($order = 'name', $sort = 'asc')
    {
        return $this->belongsToMany(Location::class)
            ->using(LocationProduct::class)
            ->withPivot([
                'quantity_on_hand',
                'quantity_reserved_for_picking'
            ])
            ->orderBy($order, $sort);
    }

    public function replenishmentLocations()
    {
        return $this->locations('location_product.quantity_on_hand', 'desc')
            ->where('pickable_effective', 0)
            ->wherePivot('quantity_on_hand', '>', 0)
            ->get();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function inventoryLogs()
    {
        return $this->hasMany(InventoryLog::class);
    }

    public function productImages()
    {
        return $this->morphMany(Image::class, 'object');
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'object')->withTrashed();
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class);
    }

    public function kits(): HasMany
    {
        return $this->hasMany(KitItem::class,
            'child_product_id'
        );
    }

    public function components(): HasMany
    {
        return $this->hasMany(KitItem::class,
            'parent_product_id'
        );
    }

    /**
     * @deprecated should start using components() relation
     * @return BelongsToMany
     */
    public function kitItems()
    {
        return $this->belongsToMany(__CLASS__, 'kit_items', 'parent_product_id', 'child_product_id')
            ->using(KitItem::class)
            ->withPivot(['quantity']);
    }

    /**
     * @deprecated should start using kits() relation
     * @return BelongsToMany
     */
    public function kitParents()
    {
        return $this->belongsToMany(__CLASS__, 'kit_items', 'child_product_id', 'parent_product_id')->withPivot(['quantity']);
    }

    public function country(): HasOne
    {
        return $this->hasOne(Countries::class, 'id', 'country_of_origin');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function lots()
    {
        return $this->hasMany(Lot::class);
    }

    public function lotItems()
    {
        return $this->hasManyThrough(LotItem::class, Lot::class);
    }

    public function placedLotItems()
    {
        return $this->hasMany(LotItem::class)->where('quantity_remaining', '>', 0);
    }

    /**
     * @return HasMany
     */
    public function productWarehouses(): HasMany
    {
        return $this->hasMany(ProductWarehouse::class);
    }


    /**
     * @param $value
     */
    public function setReorderThresholdAttribute($value)
    {
        $this->attributes['reorder_threshold'] = (int)$value;
    }

    /**
     * @param $value
     */
    public function setQuantityReorderAttribute($value)
    {
        $this->attributes['quantity_reorder'] = (int)$value;
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

    /**
     * @return bool
     */
    public function isKit(): bool
    {
        return $this->type === self::PRODUCT_TYPE_STATIC_KIT || $this->type === self::PRODUCT_TYPE_DYNAMIC_KIT;
    }

    /**
     * @return bool
     */
    public function isKitItem(): bool
    {
        return DB::table('kit_items')
                ->where('child_product_id', $this->id)
                ->count() > 0;
    }

    public function calculateQuantityInbound()
    {
        $quantity = 0;
        $quantityReceived = 0;

        foreach ($this->purchaseOrderLineActive as $poLine) {
            $quantity += $poLine->quantity;
            $quantityReceived += $poLine->quantity_received;
        }

        $this->quantity_inbound = max(0, $quantity - $quantityReceived);
        $this->update();
    }

    public function getSortedLocationsQuery(Warehouse $warehouse = null)
    {
        $customerParentId = $this->customer->parent_id;

        $pickingRouteStrategy = customer_settings($this->customer_id, CustomerSetting::CUSTOMER_SETTING_PICKING_ROUTE_STRATEGY);

        if (is_null($pickingRouteStrategy) && $customerParentId) {
            $pickingRouteStrategy = customer_settings($customerParentId, CustomerSetting::CUSTOMER_SETTING_PICKING_ROUTE_STRATEGY);
        }

        $pickingLotConfiguration = $this->lot_priority ?? customer_settings($this->customer_id, CustomerSetting::CUSTOMER_SETTING_LOT_PRIORITY);

        if (is_null($pickingLotConfiguration) && $customerParentId) {
            $pickingLotConfiguration = customer_settings($customerParentId, CustomerSetting::CUSTOMER_SETTING_LOT_PRIORITY);
        }

        $locations = $this->locations()
            ->reorder()
            ->where('pickable_effective', 1)
            ->where('disabled_on_picking_app_effective', 0);

        if ($warehouse && Feature::for('instance')->active(MultiWarehouse::class)) {
            $locations->where('warehouse_id', $warehouse->id);
        }

        if ($pickingLotConfiguration) {
            switch ($pickingLotConfiguration) {
                case LotPriority::FIFO->value:
                    $locations->orderByPivot('created_at');
                    break;
                case LotPriority::LIFO->value:
                    $locations->orderByPivot('created_at', 'desc');
                    break;
                case LotPriority::FEFO->value:
                    $locations->withAggregate('lots', 'expiration_date')
                        ->orderBy('lots_expiration_date');
                    break;
            }
        }

        return match ($pickingRouteStrategy) {
            RouteOptimizationComponent::PICKING_STRATEGY_MOST_INVENTORY => $locations
                ->orderBy('pivot_quantity_on_hand', 'desc'),
            RouteOptimizationComponent::PICKING_STRATEGY_LEAST_INVENTORY => $locations
                ->orderBy('pivot_quantity_on_hand'),
            default => $locations->orderBy('name'),
        };
    }

    public function suppliersLink()
    {
        $suppliers = '';

        foreach ($this->suppliers as $supplier) {
            $suppliers .= '<a href="' . route('supplier.index') . '#editModal-' . $supplier->id . '" target="_blank">' . $supplier->contactInformation->name . '</a><br/>';
        }

        return $suppliers;
    }

    public function barcodes()
    {
        $barcodes = $this->productBarcodes->pluck('barcode')->toArray();
        $barcodes[] = $this->barcode;

        return array_unique($barcodes);
    }
}
