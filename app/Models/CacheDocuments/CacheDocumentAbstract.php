<?php

namespace App\Models\CacheDocuments;

use App\Models\BillingRate;
use App\Models\Shipment;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

abstract class CacheDocumentAbstract extends Model
{
    use SoftDeletes;

    protected $connection = 'mongodb';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        // Add common fillable properties here
        'shipment',
        'order_id',
        'customer_id',
        'charges'
    ];

    public function buildFromData(int $order_id, int $customerId): void
    {
        $this->order_id = $order_id;
        $this->customer_id = $customerId;
        // all other relevant common data for all cache in here
    }

    /**
     * @param BillingRate $billingRate
     * @return array
     */
    public function getRateArray(BillingRate $billingRate): array
    {
        return [
            'rate_card_id' => $billingRate->rate_card_id,
            'id' => $billingRate->id,
            'name' => $billingRate->name,
            'type' => $billingRate->type,
            'settings' => $billingRate->settings,
            'updated_at' => $billingRate->updated_at->toIso8601String()
        ];
    }

    public function getCharges()
    {
        return $this->charges;
    }
}
