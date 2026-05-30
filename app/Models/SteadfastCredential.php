<?php

namespace App\Models;

use App\Interfaces\ShippingProviderCredential;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\SteadfastCredential
 *
 * @property int $id
 * @property int $customer_id
 * @property string $api_base_url
 * @property string $api_key
 * @property string $secret_key
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Customer $customer
 */
class SteadfastCredential extends Model implements ShippingProviderCredential
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['shippingCarriers'];

    protected $fillable = [
        'customer_id',
        'api_base_url',
        'api_key',
        'secret_key',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shippingMethods(): MorphMany
    {
        return $this->morphMany(ShippingMethod::class, 'credential');
    }

    public function shippingCarriers(): MorphMany
    {
        return $this->morphMany(ShippingCarrier::class, 'credential');
    }
}
