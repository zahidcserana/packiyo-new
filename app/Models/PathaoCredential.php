<?php

namespace App\Models;

use App\Interfaces\ShippingProviderCredential;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Dyrynda\Database\Support\CascadeSoftDeletes;

/**
 * App\Models\PathaoCredential
 *
 * @property int $id
 * @property int $customer_id
 * @property string $api_base_url
 * @property string $client_id
 * @property string $client_secret
 * @property string $username
 * @property string $password
 * @property int $store_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Customer $customer
 * @method static Builder|PathaoCredential newModelQuery()
 * @method static Builder|PathaoCredential newQuery()
 * @method static \Illuminate\Database\Query\Builder|PathaoCredential onlyTrashed()
 * @method static Builder|PathaoCredential query()
 * @method static Builder|PathaoCredential whereApiBaseUrl($value)
 * @method static Builder|PathaoCredential whereClientId($value)
 * @method static Builder|PathaoCredential whereClientSecret($value)
 * @method static Builder|PathaoCredential whereUsername($value)
 * @method static Builder|PathaoCredential wherePassword($value)
 * @method static Builder|PathaoCredential whereStoreId($value)
 * @method static Builder|PathaoCredential whereCreatedAt($value)
 * @method static Builder|PathaoCredential whereCustomerId($value)
 * @method static Builder|PathaoCredential whereDeletedAt($value)
 * @method static Builder|PathaoCredential whereId($value)
 * @method static Builder|PathaoCredential whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|PathaoCredential withTrashed()
 * @method static \Illuminate\Database\Query\Builder|PathaoCredential withoutTrashed()
 * @mixin \Eloquent
 */
class PathaoCredential extends Model implements ShippingProviderCredential
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = [
        'shippingCarriers'
    ];

    protected $fillable = [
        'customer_id',
        'api_base_url',
        'client_id',
        'client_secret',
        'username',
        'password',
        'store_id',
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
