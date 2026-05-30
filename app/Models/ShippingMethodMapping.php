<?php

namespace App\Models;

use Database\Factories\ShippingMethodMappingFactory;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Venturecraft\Revisionable\RevisionableTrait;

/**
 * App\Models\ShippingMethodMapping
 *
 * @property int $id
 * @property int $customer_id
 * @property int|null $shipping_method_id
 * @property int|null $return_shipping_method_id
 * @property string $shipping_method_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Customer|null $customer
 * @property-read Collection|\Venturecraft\Revisionable\Revision[] $revisionHistory
 * @property-read int|null $revision_history_count
 * @property-read ShippingMethod|null $shippingMethod
 * @method static ShippingMethodMappingFactory factory(...$parameters)
 * @method static Builder|ShippingMethodMapping newModelQuery()
 * @method static Builder|ShippingMethodMapping newQuery()
 * @method static \Illuminate\Database\Query\Builder|ShippingMethodMapping onlyTrashed()
 * @method static Builder|ShippingMethodMapping query()
 * @method static Builder|ShippingMethodMapping whereCreatedAt($value)
 * @method static Builder|ShippingMethodMapping whereCustomerId($value)
 * @method static Builder|ShippingMethodMapping whereDeletedAt($value)
 * @method static Builder|ShippingMethodMapping whereId($value)
 * @method static Builder|ShippingMethodMapping whereReturnShippingMethodId($value)
 * @method static Builder|ShippingMethodMapping whereShippingMethodId($value)
 * @method static Builder|ShippingMethodMapping whereShippingMethodName($value)
 * @method static Builder|ShippingMethodMapping whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|ShippingMethodMapping withTrashed()
 * @method static \Illuminate\Database\Query\Builder|ShippingMethodMapping withoutTrashed()
 * @mixin \Eloquent
 * @property string|null $type
 * @property-read ShippingMethod|null $returnShippingMethod
 * @method static Builder|ShippingMethodMapping whereType($value)
 */
class ShippingMethodMapping extends Model
{
    use HasFactory, SoftDeletes, RevisionableTrait;

    protected $fillable = [
        'customer_id',
        'shipping_method_id',
        'return_shipping_method_id',
        'shipping_method_name',
        'type'
    ];

    public const CHEAPEST_SHIPPING = 'cheapest';
    public const CHEAPEST_SHIPPING_ONE_DAY = 'cheapest-1day';
    public const CHEAPEST_SHIPPING_TWO_DAYS = 'cheapest-2days';
    public const CHEAPEST_SHIPPING_ONE_THREE_DAYS = 'cheapest-1-3days';
    public const CHEAPEST_SHIPPING_THREE_FIVE_DAYS = 'cheapest-3-5days';

    public const CHEAPEST_SHIPPING_METHODS = [
        self::CHEAPEST_SHIPPING => 'Cheapest Shipping',
        // self::CHEAPEST_SHIPPING_ONE_DAY => 'Cheapest 1 Day Shipping',
        // self::CHEAPEST_SHIPPING_TWO_DAYS => 'Cheapest 2 Day Shipping',
        // self::CHEAPEST_SHIPPING_ONE_THREE_DAYS => 'Cheapest 1-3 Day Shipping',
        // self::CHEAPEST_SHIPPING_THREE_FIVE_DAYS => 'Cheapest 3-5 Day Shipping'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class)->withTrashed();
    }

    public function returnShippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class, 'return_shipping_method_id', 'id')->withTrashed();
    }

    public function shippingMethodName(): string
    {
        if ($this->shippingMethod) {
            return $this->shippingMethod->getCarrierNameAndNameAttribute();
        } else if ($this->type && $typeName = Arr::get(self::CHEAPEST_SHIPPING_METHODS, $this->type)) {
            return $typeName;
        }

        return '';
    }
}
