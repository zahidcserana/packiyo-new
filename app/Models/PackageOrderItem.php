<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\PackageOrderItem
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int $order_item_id
 * @property int $package_id
 * @property int|null $location_id
 * @property int|null $tote_id
 * @property int $quantity
 * @property string|null $serial_number
 * @property int|null $lot_id
 * @property-read Location|null $location
 * @property-read Lot|null $lot
 * @property-read OrderItem $orderItem
 * @property-read Package $package
 * @property-read Tote|null $tote
 * @method static Builder|PackageOrderItem newModelQuery()
 * @method static Builder|PackageOrderItem newQuery()
 * @method static \Illuminate\Database\Query\Builder|PackageOrderItem onlyTrashed()
 * @method static Builder|PackageOrderItem query()
 * @method static Builder|PackageOrderItem whereCreatedAt($value)
 * @method static Builder|PackageOrderItem whereDeletedAt($value)
 * @method static Builder|PackageOrderItem whereId($value)
 * @method static Builder|PackageOrderItem whereLocationId($value)
 * @method static Builder|PackageOrderItem whereLotId($value)
 * @method static Builder|PackageOrderItem whereOrderItemId($value)
 * @method static Builder|PackageOrderItem wherePackageId($value)
 * @method static Builder|PackageOrderItem whereQuantity($value)
 * @method static Builder|PackageOrderItem whereSerialNumber($value)
 * @method static Builder|PackageOrderItem whereToteId($value)
 * @method static Builder|PackageOrderItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|PackageOrderItem withTrashed()
 * @method static \Illuminate\Database\Query\Builder|PackageOrderItem withoutTrashed()
 * @mixin \Eloquent
 */
class PackageOrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_item_id',
        'package_id',
        'quantity',
        'serial_number',
        'location_id',
        'tote_id',
        'lot_id'
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class)->withTrashed();
    }

    public function package()
    {
        return $this->belongsTo(Package::class)->withTrashed();
    }

    public function location()
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    public function tote()
    {
        return $this->belongsTo(Tote::class)->withTrashed();
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class)->withTrashed();
    }
}
