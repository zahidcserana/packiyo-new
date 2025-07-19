<?php

namespace App\Models\CacheDocuments;

use App\Models\BillingRate;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class PurchaseOrderChargeCacheDocument extends Model implements ChargeCacheDocumentInterface
{
    use SoftDeletes;

    protected $connection = 'mongodb';

    protected $fillable = [
        'purchase_order_id',
        'purchase_order_number',
        'customer',
        'warehouse',
        'billing_rate',
        'charged_at',
        'description',
        'charge',
    ];

    public static function build(
        PurchaseOrderCacheDocument $cacheDocument,
        BillingRate $billingRate,
        float $fee,
        float $totalItems,
        float $totalCharge,
        string $description,
    ): static
    {
        return static::create([
            'customer' => $cacheDocument['customer'],
            'warehouse' => $cacheDocument['warehouse'],
            'purchase_order_id' => $cacheDocument['purchase_order_id'],
            'purchase_order_number' => $cacheDocument['purchase_order_number'],
            'billing_rate' =>[
                'id' => $billingRate->id,
                'rate_card_id' => $billingRate->rate_card_id,
                'name' => $billingRate->name,
                'type' => $billingRate->type,
                'settings'=>$billingRate->settings,
                'updated_at' => $billingRate->updated_at
            ],
            'rate_card'=>[
                'id' => $billingRate->rateCard->id,
                'name' => $billingRate->rateCard->name
            ],
            'charged_at' => now()->toISOString(),
            'description' => $description,
            'charge' => [
                'quantity' => $totalItems,
                'total_charge' => $totalCharge,
                'fee' => $fee
            ],
        ]);
    }

    public function getCharges(): array
    {
        return [
            'billing_rate_id' => $this->billing_rate['id'],
            'description' => $this->description,
            'quantity' => (int)$this->charge['quantity'],
            'charge_per_unit' => $this->charge['fee'],
            'total_charge' => $this->charge['total_charge'],
            'purchase_order_item_id' => null,
            'purchase_order_id' =>  null,
            'return_item_id' => null,
            'package_id' =>  null,
            'package_item_id' =>  null,
            'shipment_id' =>  null,
            'location_type_id' =>  null
        ];
    }

    public function getBillingRate()
    {
        return $this->billing_rate;
    }
}
