<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, Relations\BelongsTo, SoftDeletes};

/**
 * App\Models\LotItem
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $lot_id
 * @property int $location_id
 * @property int $quantity_added
 * @property int $quantity_removed
 * @property int $quantity_remaining
 * @property int|null $product_id
 * @property-read mixed $product_sku_and_lot_name
 * @property-read \App\Models\Location $location
 * @property-read \App\Models\Lot $lot
 * @property-read \App\Models\Product|null $product
 * @method static \Illuminate\Database\Eloquent\Builder|LotItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LotItem newQuery()
 * @method static \Illuminate\Database\Query\Builder|LotItem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|LotItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|LotItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LotItem whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LotItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LotItem whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LotItem whereLotId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LotItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LotItem whereQuantityAdded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LotItem whereQuantityRemaining($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LotItem whereQuantityRemoved($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LotItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|LotItem withTrashed()
 * @method static \Illuminate\Database\Query\Builder|LotItem withoutTrashed()
 * @mixin \Eloquent
 */
class LotItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lot_id',
        'location_id',
        'quantity_added',
        'quantity_removed',
        'quantity_remaining'
    ];

    public function lot()
    {
        return $this->belongsTo(Lot::class)->withTrashed();
    }

    public function location()
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function getProductSkuAndLotNameAttribute()
    {
        return $this->lot->product->sku . ' - ' . $this->lot->name;
    }
}
