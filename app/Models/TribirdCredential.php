<?php

namespace App\Models;

use App\Interfaces\ShippingProviderCredential;
use App\Interfaces\OrderChannelProviderCredential;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TribirdCredential extends Model implements ShippingProviderCredential, OrderChannelProviderCredential
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = [
        'shippingCarriers'
    ];

    protected $fillable = [
        'customer_id',
        'settings'
    ];

    protected $casts = [
        'settings' => 'array'
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
