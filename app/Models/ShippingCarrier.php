<?php

namespace App\Models;

use App\Interfaces\SoftDeletableSluggable;
use Database\Factories\ShippingCarrierFactory;
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
 * App\Models\ShippingCarrier
 *
 * @property int $id
 * @property int $customer_id
 * @property string $credential_type
 * @property int $credential_id
 * @property string $carrier_service
 * @property string $name
 * @property array $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string|null $carrier_account
 * @property int $active
 * @property-read Model|\Eloquent $credential
 * @property-read Customer $customer
 * @property-read Collection|\Venturecraft\Revisionable\Revision[] $revisionHistory
 * @property-read int|null $revision_history_count
 * @property-read Collection|ShippingMethod[] $shippingMethods
 * @property-read int|null $shipping_methods_count
 * @method static ShippingCarrierFactory factory(...$parameters)
 * @method static Builder|ShippingCarrier newModelQuery()
 * @method static Builder|ShippingCarrier newQuery()
 * @method static \Illuminate\Database\Query\Builder|ShippingCarrier onlyTrashed()
 * @method static Builder|ShippingCarrier query()
 * @method static Builder|ShippingCarrier whereActive($value)
 * @method static Builder|ShippingCarrier whereCarrierAccount($value)
 * @method static Builder|ShippingCarrier whereCarrierService($value)
 * @method static Builder|ShippingCarrier whereCreatedAt($value)
 * @method static Builder|ShippingCarrier whereCredentialId($value)
 * @method static Builder|ShippingCarrier whereCredentialType($value)
 * @method static Builder|ShippingCarrier whereCustomerId($value)
 * @method static Builder|ShippingCarrier whereDeletedAt($value)
 * @method static Builder|ShippingCarrier whereId($value)
 * @method static Builder|ShippingCarrier whereName($value)
 * @method static Builder|ShippingCarrier whereSettings($value)
 * @method static Builder|ShippingCarrier whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|ShippingCarrier withTrashed()
 * @method static \Illuminate\Database\Query\Builder|ShippingCarrier withoutTrashed()
 * @mixin \Eloquent
 */
class ShippingCarrier extends Model implements SoftDeletableSluggable
{
    use HasFactory, SoftDeletes, CascadeSoftDeletes, RevisionableTrait;

    protected $cascadeDeletes = [
        'shippingMethods'
    ];

    protected $fillable = [
        'customer_id',
        'carrier_service',
        'name',
        'settings',
        'carrier_account',
        'active',
        'integration'
    ];

    protected $casts = [
        'settings' => 'array'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function credential()
    {
        return $this->morphTo()->withTrashed();
    }

    public function shippingMethods()
    {
        return $this->hasMany(ShippingMethod::class);
    }

    public function slugify(): string
    {
        return Str::slug($this->name);
    }

    /**
     * Map stored carrier name to actual shipping company
     */
    public function carrierCompany(): string
    {
        $nameToMatch = str_replace('_', '', strtolower($this->name));

        if (Str::contains($nameToMatch, 'fedex')) {
            return 'FedEx';
        } else if (Str::contains($nameToMatch, 'fx grd econ')) {
            return 'FedEx';
        } else if (Str::contains($nameToMatch, 'gso')) {
            return 'GLS US';
        } else if (Str::contains($nameToMatch, 'dhlec')) {
            return 'DHL eCommerce';
        } else if (Str::contains($nameToMatch, 'dhlexpress')) {
            return 'DHL Express';
        } else if (Str::startsWith($nameToMatch, 'ups')) {
            return 'UPS';
        } else if (Str::startsWith($nameToMatch, 'firstmile') || Str::startsWith($nameToMatch, 'first mile')) {
            return 'First Mile';
        }

        return $this->name;
    }

    public function getNameAndIntegrationAttribute()
    {
        return $this->name . ($this->integration ? ' (' . $this->integration . ')' : '');
    }
}
