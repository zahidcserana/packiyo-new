<?php

namespace App\Models;

use Database\Factories\ProductWarehouseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * App\Models\ProductWarehouse
 *
 * @property int $id
 * @property int $product_id
 * @property int $warehouse_id
 * @property int $quantity_on_hand
 * @property int $quantity_reserved
 * @property int $quantity_pickable
 * @property int $quantity_allocated
 * @property int $quantity_allocated_pickable
 * @property int $quantity_available
 * @property int $quantity_to_replenish
 * @property int $quantity_backordered
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|ProductWarehouse newModelQuery()
 * @method static Builder|ProductWarehouse newQuery()
 * @method static Builder|ProductWarehouse query()
 * @method static Builder|ProductWarehouse whereCreatedAt($value)
 * @method static Builder|ProductWarehouse whereId($value)
 * @method static Builder|ProductWarehouse whereProductId($value)
 * @method static Builder|ProductWarehouse whereQuantityAllocated($value)
 * @method static Builder|ProductWarehouse whereQuantityAllocatedPickable($value)
 * @method static Builder|ProductWarehouse whereQuantityAvailable($value)
 * @method static Builder|ProductWarehouse whereQuantityBackordered($value)
 * @method static Builder|ProductWarehouse whereQuantityOnHand($value)
 * @method static Builder|ProductWarehouse whereQuantityPickable($value)
 * @method static Builder|ProductWarehouse whereQuantityReserved($value)
 * @method static Builder|ProductWarehouse whereQuantityToReplenish($value)
 * @method static Builder|ProductWarehouse whereUpdatedAt($value)
 * @method static Builder|ProductWarehouse whereWarehouseId($value)
 * @property int $quantity_sell_ahead
 * @property int $quantity_inbound
 * @property-read Product $product
 * @property-read Warehouse $warehouse
 * @method static Builder|ProductWarehouse whereQuantityInbound($value)
 * @method static Builder|ProductWarehouse whereQuantitySellAhead($value)
 * @property int $quantity_non_sellable
 * @method static ProductWarehouseFactory factory(...$parameters)
 * @method static Builder|ProductWarehouse whereQuantityNonSellable($value)
 * @mixin \Eloquent
 */
class ProductWarehouse extends Pivot
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity_on_hand',
        'quantity_reserved',
        'quantity_pickable',
        'quantity_allocated',
        'quantity_allocated_pickable',
        'quantity_available',
        'quantity_to_replenish',
        'quantity_backordered',
        'quantity_sell_ahead',
        'quantity_inbound',
        'quantity_non_sellable'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
