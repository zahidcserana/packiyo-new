<?php

namespace App\Models;

use App\Interfaces\ShippingProviderCredential;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\EasypostCredential
 *
 * @property int $id
 * @property int $customer_id
 * @property string $api_key
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string|null $test_api_key
 * @property string|null $reference_prefix
 * @property int $use_native_tracking_urls
 * @property string|null $commercial_invoice_signature
 * @property string|null $commercial_invoice_letterhead
 * @property string|null $endorsement
 * @property-read \App\Models\Customer $customer
 * @property-read Collection|\App\Models\ShippingCarrier[] $shippingCarriers
 * @property-read int|null $shipping_carriers_count
 * @method static Builder|EasypostCredential newModelQuery()
 * @method static Builder|EasypostCredential newQuery()
 * @method static \Illuminate\Database\Query\Builder|EasypostCredential onlyTrashed()
 * @method static Builder|EasypostCredential query()
 * @method static Builder|EasypostCredential whereApiKey($value)
 * @method static Builder|EasypostCredential whereCommercialInvoiceLetterhead($value)
 * @method static Builder|EasypostCredential whereCommercialInvoiceSignature($value)
 * @method static Builder|EasypostCredential whereCreatedAt($value)
 * @method static Builder|EasypostCredential whereCustomerId($value)
 * @method static Builder|EasypostCredential whereDeletedAt($value)
 * @method static Builder|EasypostCredential whereId($value)
 * @method static Builder|EasypostCredential whereReferencePrefix($value)
 * @method static Builder|EasypostCredential whereTestApiKey($value)
 * @method static Builder|EasypostCredential whereUpdatedAt($value)
 * @method static Builder|EasypostCredential whereUseNativeTrackingUrls($value)
 * @method static \Illuminate\Database\Query\Builder|EasypostCredential withTrashed()
 * @method static \Illuminate\Database\Query\Builder|EasypostCredential withoutTrashed()
 * @mixin \Eloquent
 */
class EasypostCredential extends Model implements ShippingProviderCredential
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = [
        'shippingCarriers'
    ];

    protected $fillable = [
        'customer_id',
        'api_key',
        'test_api_key',
        'reference_prefix',
        'use_native_tracking_urls',
        'commercial_invoice_signature',
        'commercial_invoice_letterhead',
        'endorsement',
    ];

    public const ENDORSEMENT = [
        '0' => 'None',
        'ELECTRONIC_SERVICE_REQUESTED' => 'Electronic service requested',
        'TEMP_RETURN_SERVICE_REQUESTED' => 'Temp return service requested',
        'NO_RETURN_TO_SENDER' => 'No return to sender',
        'ADDRESS_SERVICE_REQUESTED' => 'Address service requested',
        'CHANGE_SERVICE_REQUESTED' => 'Change service requested',
        'FORWARDING_SERVICE_REQUESTED' => 'Forwarding service requested',
        'LEAVE_IF_NO_RESPONSE' => 'Leave if no response',
        'RETURN_SERVICE_REQUESTED' => 'Return service requested'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shippingCarriers(): MorphMany
    {
        return $this->morphMany(ShippingCarrier::class, 'credential');
    }
}
