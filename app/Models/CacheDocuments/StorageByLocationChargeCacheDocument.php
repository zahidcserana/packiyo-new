<?php

namespace App\Models\CacheDocuments;

use App\Components\BillingRates\Charges\StorageByLocation\BillingPeriod;
use App\Models\BillingRate;
use App\Models\LocationType;
use App\Models\RateCard;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class StorageByLocationChargeCacheDocument extends Model implements ChargeCacheDocumentInterface
{
    use SoftDeletes;

    protected $connection = 'mongodb';

    // Why not casting https://stackoverflow.com/questions/6764821/what-is-the-best-way-to-store-dates-in-mongodb
    // protected $casts = ['date' => 'datetime'];

    protected $fillable = [
        'customer_id',
        'customer',
        'warehouse_id',
        'warehouse',
        'rate_card_id',
        'rate_card',
        'billing_rate_id',
        'billing_rate',
        'charged_at',
        'period_occupied',
        'charge',
        'description',
        'location_type_id',
        'location_type',
    ];

    public static function build(
        int $customerId,
        int $warehouseId,
        string $warehouseName,
        RateCard $rateCard,
        BillingRate $billingRate,
        LocationType $locationType,
        int $quantity,
        BillingPeriod $period,
        string $description
    ): static
    {
        return static::make([
            'customer_id' => $customerId,
            'warehouse_id' => $warehouseId,
            'warehouse' => [
                'name' => $warehouseName,
            ],
            'rate_card_id' => $rateCard->id,
            'billing_rate_id' => $billingRate->id,
            'billing_rate' => [
                'id' => $billingRate->id,
                'name' => $billingRate->name,
                'type' => $billingRate->type,
                'code' => $billingRate->code,
                'fee' => $billingRate->settings['fee'],
                'time_unit' => $billingRate->settings['period'],
                'updated_at' => $billingRate->updated_at->toIso8601String(),
            ],
            'charged_at' => now()->toISOString(),
            'period_occupied' => [
                'client_timezone' => $period->timezone->getName(),
                'utc_from' => $period->from->utc()->toISOString(),
                'utc_to' => $period->to->utc()->toISOString(),
            ],
            'description' => $description,
            'charge' => [
                'quantity' => $quantity,
                'fee' => $billingRate->settings['fee'],
                'total_charge' => $quantity * $billingRate->settings['fee'],
            ],
            'location_type_id' => $locationType->id,
            'location_type' => [
                'name' => $locationType->name,
                'sellable' => $locationType->sellable,
                'pickable' => $locationType->pickable,
                'bulk_ship_pickable' => $locationType->bulk_ship_pickable,
                'disabled_on_picking_app' => $locationType->disabled_on_picking_app,
            ]
        ]);
    }

    public function scopePeriod(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query
            ->where('period_occupied.utc_from', $from->startOfDay()->utc()->toISOString())
            ->where('period_occupied.utc_to', $to->endOfDay()->utc()->toISOString());
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
            'purchase_order_id' => null,
            'return_item_id' => null,
            'package_id' => null,
            'package_item_id' => null,
            'shipment_id' => null,
            'location_type_id' => null
        ];
    }

    public function getBillingRate()
    {
        return $this->billing_rate;
    }
}
