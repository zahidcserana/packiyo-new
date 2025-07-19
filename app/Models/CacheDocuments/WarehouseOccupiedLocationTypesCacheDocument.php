<?php

namespace App\Models\CacheDocuments;

use App\Models\Customer;
use App\Models\Warehouse;
use Carbon\Carbon;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class WarehouseOccupiedLocationTypesCacheDocument extends Model implements CacheDocumentInterface
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
        'location_types',
        'calendar_date',
        'timezone',
        'calculated_billing_rates'
    ];

    protected $casts = [
        'calendar_date' => 'datetime:Y-m-d',
    ];

    public static function buildFromModels(
        Customer $customer, Warehouse $warehouse, Carbon $calendarDate, array $locations
    ): static
    {
        return static::make([
            'customer_id' => $customer->id,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->contactInformation->name
            ],
            'warehouse_id' => $warehouse->id,
            'warehouse' => [
                'id' => $warehouse->id,
                'name' => $warehouse->contactInformation->name,
                'country' => $warehouse->contactInformation->country->iso_3166_2,
                'state' => $warehouse->contactInformation->state,
                'city' => $warehouse->contactInformation->city
            ],
            'location_types' => $locations,
            'calendar_date' => $calendarDate->toDateString(),
            'timezone' => $calendarDate->timezone->getName(),
            'calculated_billing_rates' => []
        ]);
    }

    public function getCalculatedBillingRates()
    {
        return $this->calculated_billing_rates;
    }

    public function getDocumentInformation(): string
    {
        return sprintf("warehouse id: %s, customer id: %s, calendar date: %s", $this->warehouse_id, $this->customer_id, $this->calendar_date);
    }
}
