<?php

namespace App\Models;

use App\Interfaces\SoftDeletableSluggable;
use Database\Factories\ShippingMethodFactory;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Venturecraft\Revisionable\RevisionableTrait;


/**
 * App\Models\ShippingMethod
 *
 * @property int $id
 * @property int $shipping_carrier_id
 * @property string $name
 * @property array|null $settings
 * @property string|null $incoterms
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read mixed $carrier_name_and_name
 * @property-read Collection|\Venturecraft\Revisionable\Revision[] $revisionHistory
 * @property-read int|null $revision_history_count
 * @property-read \App\Models\ShippingCarrier $shippingCarrier
 * @property-read Collection|\App\Models\Tag[] $tags
 * @property-read int|null $tags_count
 * @method static ShippingMethodFactory factory(...$parameters)
 * @method static Builder|ShippingMethod newModelQuery()
 * @method static Builder|ShippingMethod newQuery()
 * @method static \Illuminate\Database\Query\Builder|ShippingMethod onlyTrashed()
 * @method static Builder|ShippingMethod query()
 * @method static Builder|ShippingMethod whereCreatedAt($value)
 * @method static Builder|ShippingMethod whereDeletedAt($value)
 * @method static Builder|ShippingMethod whereId($value)
 * @method static Builder|ShippingMethod whereIncoterm($value)
 * @method static Builder|ShippingMethod whereName($value)
 * @method static Builder|ShippingMethod whereSettings($value)
 * @method static Builder|ShippingMethod whereShippingCarrierId($value)
 * @method static Builder|ShippingMethod whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|ShippingMethod withTrashed()
 * @method static \Illuminate\Database\Query\Builder|ShippingMethod withoutTrashed()
 * @mixin \Eloquent
 * @property string|null $source
 * @property-read Collection|ShippingMethodMapping[] $returnShippingMethodMappings
 * @property-read int|null $return_shipping_method_mappings_count
 * @property-read Collection|ShippingMethodMapping[] $shippingMethodMappings
 * @property-read int|null $shipping_method_mappings_count
 * @method static Builder|ShippingMethod whereIncoterms($value)
 * @method static Builder|ShippingMethod whereSource($value)
 */
class ShippingMethod extends Model implements SoftDeletableSluggable
{
    use HasFactory, SoftDeletes, CascadeSoftDeletes, RevisionableTrait;

    public const DYNAMICALLY_ADDED = 'dynamically_added';

    protected $cascadeDeletes = [
        'shippingMethodMappings',
        'returnShippingMethodMappings'
    ];

    protected $fillable = [
        'shipping_carrier_id',
        'name',
        'settings',
        'incoterms',
        'source'
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function getCarrierNameAndNameAttribute()
    {
        return $this->shippingCarrier->getNameAndIntegrationAttribute() . ' - ' . $this->name;
    }

    public function shippingCarrier()
    {
        return $this->belongsTo(ShippingCarrier::class)->withTrashed();
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function shippingMethodMappings()
    {
        return $this->hasMany(ShippingMethodMapping::class);
    }

    public function returnShippingMethodMappings()
    {
        return $this->hasMany(ShippingMethodMapping::class, 'return_shipping_method_id', 'id');
    }

    public function delete()
    {
        Order::whereNull('archived_at')
            ->whereNull('cancelled_at')
            ->whereNull('fulfilled_at')
            ->where('shipping_method_id', $this->id)
            ->update(['shipping_method_id' => null]);

        return parent::delete();
    }

    public function slugify(): string
    {
        return Str::slug(sprintf("%s-%s",$this->shippingCarrier->name, $this->name));
    }
}
