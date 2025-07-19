<?php

namespace App\Models;

use App\Interfaces\ShippingProviderCredential;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\ExternalCarrierCredential
 *
 * @property int $id
 * @property int $customer_id
 * @property string|null $reference
 * @property string|null $get_carriers_url
 * @property string|null $create_shipment_label_url
 * @property string|null $create_return_label_url
 * @property string|null $void_label_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Customer $customer
 * @method static \Illuminate\Database\Eloquent\Builder|ExternalCarrierCredential newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ExternalCarrierCredential newQuery()
 * @method static \Illuminate\Database\Query\Builder|ExternalCarrierCredential onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ExternalCarrierCredential query()
 * @method static \Illuminate\Database\Eloquent\Builder|ExternalCarrierCredential whereCreateReturnLabelUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExternalCarrierCredential whereCreateShipmentLabelUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExternalCarrierCredential whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExternalCarrierCredential whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExternalCarrierCredential whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExternalCarrierCredential whereGetCarriersUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExternalCarrierCredential whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExternalCarrierCredential whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExternalCarrierCredential whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExternalCarrierCredential whereVoidLabelUrl($value)
 * @method static \Illuminate\Database\Query\Builder|ExternalCarrierCredential withTrashed()
 * @method static \Illuminate\Database\Query\Builder|ExternalCarrierCredential withoutTrashed()
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ShippingCarrier[] $shippingCarriers
 * @property-read int|null $shipping_carriers_count
 * @method static \Database\Factories\ExternalCarrierCredentialFactory factory(...$parameters)
 */
class ExternalCarrierCredential extends Model implements ShippingProviderCredential
{
    use HasFactory, SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = [
        'shippingCarriers'
    ];

    protected $fillable = [
        'customer_id',
        'reference',
        'get_carriers_url',
        'create_shipment_label_url',
        'create_return_label_url',
        'void_label_url',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function shippingCarriers(): MorphMany
    {
        return $this->morphMany(ShippingCarrier::class, 'credential');
    }
}
