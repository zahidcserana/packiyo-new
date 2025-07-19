<?php

namespace App\Models;

use App\Exceptions\InventoryException;
use DateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class OccupiedLocationLog extends Model // TODO: Add back.
{
     use SoftDeletes;

    protected $connection = 'mongodb';

    // Why not casting https://stackoverflow.com/questions/6764821/what-is-the-best-way-to-store-dates-in-mongodb
    // protected $casts = ['date' => 'datetime'];

    protected $fillable = [
        'product_id', // Needed for grouping.
        'product',
        'location_id', // Needed for grouping.
        'location',
        'warehouse_id',
        'warehouse',
        'calendar_date',
        'timezone',
        'inventory'
    ];

    public static function buildFromModels(
        Product $product, Location $location, Carbon $calendarDate, ?InventoryLog $latestInventoryLog = null
    ): static
    {
        if ($latestInventoryLog > $calendarDate) {
            throw new InventoryException('The inventory log cannot be more recent than the date.');
        }

        $occupiedLocationLog = static::make([
            'product_id' => $product->id,
            'product' => [
                'id' => $product->id,
                'customer' => [
                    'id' => $product->customer->id,
                    'name' => $product->customer->contactInformation->name
                ],
                'sku' => $product->sku,
                'name' => $product->name,
                'image' => $product->productImages->first()->source ?? null
            ],
            'location_id' => $location->id,
            'location' => [
                'id' => $location->id,
                'name' => $location->name,
                'type' => $location->locationType ? $location->locationType->name : null,
                'type_id' => $location->locationType ? $location->locationType->id : null,
            ],
            'warehouse_id' => $location->warehouse_id,
            'warehouse' => [
                'id' => $location->warehouse_id,
                'name' => $location->warehouse->contactInformation->name,
                'country' => $location->warehouse->contactInformation->country->iso_3166_2,
                'state' => $location->warehouse->contactInformation->state,
                'city' => $location->warehouse->contactInformation->city
            ],
            'calendar_date' => $calendarDate->toDateString(),
            'timezone' => $calendarDate->timezone->getName()
        ]);

        if (!is_null($latestInventoryLog)) {
            $occupiedLocationLog->inventory = [
                'first' => $latestInventoryLog->new_on_hand,
                'max' => $latestInventoryLog->new_on_hand,
                'last' => $latestInventoryLog->new_on_hand
            ];
        }

        return $occupiedLocationLog;
    }

    public static function buildFromPrevious(self $previous, Carbon $calendarDate): static
    {
        return static::make([
            'product_id' => $previous->product_id,
            'product' => $previous->product,
            'location_id' => $previous->location_id,
            'location' => $previous->location,
            'warehouse_id' => $previous->warehouse_id,
            'warehouse' => $previous->warehouse,
            'calendar_date' => $calendarDate->toDateString(),
            'timezone' => $calendarDate->timezone,
            'inventory' => [
                'first' => $previous->inventory['last'],
                'max' => $previous->inventory['last'],
                'last' => $previous->inventory['last']
            ]
        ]);
    }
}
