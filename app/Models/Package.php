<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

/**
 * App\Models\Package
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $order_id
 * @property int $shipping_box_id
 * @property string $weight
 * @property string $length
 * @property string $width
 * @property string $height
 * @property int $shipment_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PackageDocument[] $documents
 * @property-read int|null $documents_count
 * @property-read \App\Models\Order $order
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PackageOrderItem[] $packageOrderItems
 * @property-read int|null $package_order_items_count
 * @property-read \App\Models\Shipment $shipment
 * @property-read \App\Models\ShippingBox $shippingBox
 * @method static \Database\Factories\PackageFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Package newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Package newQuery()
 * @method static \Illuminate\Database\Query\Builder|Package onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Package query()
 * @method static \Illuminate\Database\Eloquent\Builder|Package whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Package whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Package whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Package whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Package whereLength($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Package whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Package whereShipmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Package whereShippingBoxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Package whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Package whereWeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Package whereWidth($value)
 * @method static \Illuminate\Database\Query\Builder|Package withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Package withoutTrashed()
 * @mixin \Eloquent
 */
class Package extends Model
{
    use HasFactory, SoftDeletes;

    protected const CONVERSION_FACTOR_CM_TO_IN = 0.39370079; // Aproximately 1/2.54
    protected const CONVERSION_FACTOR_IN_TO_CM = 2.54;

    protected $fillable = [
        'order_id',
        'shipping_box_id',
        'shipping_method_id',
        'weight',
        'length',
        'width',
        'height',
        'shipment_id'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class)->withTrashed();
    }

    public function shippingBox()
    {
        return $this->belongsTo(ShippingBox::class)->withTrashed();
    }

    public function packageOrderItems()
    {
        return $this->hasMany(PackageOrderItem::class);
    }

    // TODO: Add the packaging materials weight.
    public function getTotalWeight(): float|int
    {
        return $this->weight;
    }

    public function getVolumeInOz(): float|int
    {
        // TODO: Getting the dims unit should not be this hard.
        $dimensionUnit = customer_settings(
            $this->shippingBox ? $this->shippingBox->customer_id : $this->order->customer_id,
            CustomerSetting::CUSTOMER_SETTING_DIMENSIONS_UNIT,
            Customer::DIMENSION_UNIT_DEFAULT
        );
        $conversionFactor = $dimensionUnit == 'cm' ? static::CONVERSION_FACTOR_CM_TO_IN : 1;

        return ($this->length * $conversionFactor) * ($this->width * $conversionFactor) * ($this->height * $conversionFactor);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PackageDocument::class);
    }
}
