<?php

namespace App\Models\CacheDocuments;

use App\Models\PurchaseOrder;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class PurchaseOrderCacheDocument extends Model implements CacheDocumentInterface
{
    use SoftDeletes;

    protected $connection = 'mongodb';

    protected $fillable = [
        'customer',
        'warehouse',
        'purchase_order_id',
        'purchase_order_number',
        'items',
        'calculated_billing_rates'
        //      - billing_rate_id
        //      - calculated_at
        //      - charges
    ];

    public static function buildFromPurchaseOrder(
        PurchaseOrder $purchaseOrder,
    ): static
    {
        return static::create([
            'customer' => [
                'id' => $purchaseOrder->customer_id,
                'name' => $purchaseOrder->customer->name,
            ],
            'warehouse' => [
                'id' => $purchaseOrder->warehouse_id,
                'name' => $purchaseOrder->warehouse->name,
                'country' => $purchaseOrder->warehouse->country,
                'state' => $purchaseOrder->warehouse->state,
                'city' => $purchaseOrder->warehouse->city
            ],
            'purchase_order_id' => $purchaseOrder->id,
            'purchase_order_number' => $purchaseOrder->number,
            'items' => self::getPurchaseOrderItems($purchaseOrder),
            'calculated_billing_rates' => []
        ]);
    }

    private static function getPurchaseOrderItems(PurchaseOrder $purchaseOrder): array
    {
        return $purchaseOrder->purchaseOrderItems->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'quantity_received' => $item->quantity_received,
                'quantity_pending' => $item->quantity_pending,
                'quantity_rejected' => $item->quantity_rejected,
                'quantity_sell_ahead' => $item->quantity_sell_ahead,
            ];
        })->toArray();
    }

    public function getCalculatedBillingRates()
    {
        return $this->calculated_billing_rates;
    }
}
