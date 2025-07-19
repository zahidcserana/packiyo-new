<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};

/**
 * App\Models\Lot
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $expiration_date
 * @property int $customer_id
 * @property int $product_id
 * @property string $item_price
 * @property int $supplier_id
 * @property-read \App\Models\Customer $customer
 * @property-read mixed $name_and_expiration_date_and_supplier_name
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\LotItem[] $lotItems
 * @property-read int|null $lot_items_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PackageOrderItem[] $packageOrderItems
 * @property-read int|null $package_order_items_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\LotItem[] $placedLotItems
 * @property-read int|null $placed_lot_items_count
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\Supplier $supplier
 * @method static \Illuminate\Database\Eloquent\Builder|Lot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Lot newQuery()
 * @method static \Illuminate\Database\Query\Builder|Lot onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Lot query()
 * @method static \Illuminate\Database\Eloquent\Builder|Lot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Lot whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Lot whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Lot whereExpirationDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Lot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Lot whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Lot whereItemPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Lot whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Lot whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Lot whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Lot withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Lot withoutTrashed()
 * @mixin \Eloquent
 */
class Lot extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'expiration_date',
        'customer_id',
        'product_id',
        'supplier_id',
        'item_price',
    ];

    protected $casts = [
        'expiration_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function lotItems()
    {
        return $this->hasMany(LotItem::class)->withTrashed();
    }

    public function placedLotItems()
    {
        return $this->hasMany(LotItem::class)->where('quantity_remaining', '>', 0);
    }

    public function packageOrderItems()
    {
        return $this->hasMany(PackageOrderItem::class)->withTrashed();
    }

    public function getNameAndExpirationDateAndSupplierNameAttribute()
    {
        return $this->name . ' ' . user_date_time($this->exiration_date) . ' ' . $this->supplier->contactInformation->name;
    }
}
