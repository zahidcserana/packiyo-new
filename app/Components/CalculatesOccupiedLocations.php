<?php

namespace App\Components;

use App\Events\OccupiedLocationsCalculatedEvent;
use App\Models\{CacheDocuments\WarehouseOccupiedLocationTypesCacheDocument,
    Customer,
    InventoryLog,
    Location,
    OccupiedLocationLog,
    Product,
    UserSetting,
    Warehouse};
use Igaster\LaravelCities\Geo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

const CALCULATE_AT = '00';  // Midnight.

trait CalculatesOccupiedLocations
{
    public function calculateOccupiedLocations(?Carbon $now = null): void
    {
        if (is_null($now)) {
            $now = Carbon::now();
        }

        Customer::whereNotNull('parent_id')->each(function (Customer $customer) use ($now) {
            foreach ($customer->parent->warehouses as $warehouse) {
                $customerNow = static::getCustomerNow($warehouse, $now);

                if (static::shouldCalculateOccupiedLocations($customerNow)) {
                    $this->calculateLocationsOccupiedByCustomer($customer, $warehouse, $customerNow);
                }
            }
        });
    }

    protected static function getCustomerNow(Warehouse $warehouse, ?Carbon $now = null): Carbon
    {
        if (is_null($now)) {
            $now = Carbon::now();
        }

        return $now->copy()->setTimezone(static::getTimezone($warehouse));
    }

    protected static function shouldCalculateOccupiedLocations(Carbon $now): bool
    {
        return $now->hour == CALCULATE_AT;
    }

    protected static function getTimezone(Warehouse $warehouse): string
    {
        return static::getTimezoneByWarehouse($warehouse)
            ?? static::getTimezoneByFirstUser($warehouse->customer)
            ?? env('DEFAULT_TIME_ZONE');
    }

    protected static function getTimezoneByWarehouse(Warehouse $warehouse): ?string
    {
        $coordinates = Geo::where([
            'name' => $warehouse->contactInformation->city,
            'country' => $warehouse->contactInformation->iso_3166_2
        ])->first();

        // TODO: Find a way to do this without a library that hits an external API.
        return null;
    }

    protected static function getTimezoneByFirstUser(Customer $customer): ?string
    {
        $firstUser = $customer->users()->orderBy('created_at')->first();

        if (is_null($firstUser)) {
            return null;
        }

        return user_settings(UserSetting::USER_SETTING_TIMEZONE, user_id: $firstUser->id, default: env('DEFAULT_TIME_ZONE'));
    }

    public function calculateLocationsOccupiedByCustomer(Customer $customer, Warehouse $warehouse, Carbon $datetime, bool $dispatchEvent = true): void
    {
        // TODO: Ensure date is adjusted to customer's time zone - the day should be based on customer's midnight.
        $datetime = static::setMidnight($datetime);

        foreach ($customer->products as $product) {
            // TODO: Add "since last update" constraint - after recording last updates.
            foreach (static::getHistoricLocations($product, $warehouse) as $location) {
                $getLogsQuery = fn () => static::getInventoryLogsQuery($product, $location, $datetime);

                if ($getLogsQuery()->exists()) {
                    static::logOccupiedLocationFromInventoryLogs($product, $location, $datetime);
                } else {
                    $latestOccupiedLocationLog = static::getLatestOccupiedLocationLog($product, location: $location);
                    $startDate = static::getStartDateTime($datetime);

                    if (!is_null($latestOccupiedLocationLog) && $latestOccupiedLocationLog->inventory['last'] > 0) {
                        $occupiedLocationLog = OccupiedLocationLog::buildFromPrevious($latestOccupiedLocationLog, $startDate);
                        $occupiedLocationLog->save();
                    } else {
                        $latestInventoryLog = static::getLatestInventoryLog($product, $location, $datetime);

                        if (!is_null($latestInventoryLog) && $latestInventoryLog->new_on_hand > 0) {
                            $occupiedLocationLog = OccupiedLocationLog::buildFromModels(
                                $product, $location, $startDate, $latestInventoryLog
                            );
                            $occupiedLocationLog->save();
                        }
                    }
                }
            }
        }

        if ($dispatchEvent) {
            event(new OccupiedLocationsCalculatedEvent(
                $customer,
                $warehouse,
                static::getStartDateTime($datetime)
            ));
        }
    }

    protected static function setMidnight(Carbon $datetime): Carbon
    {
        return $datetime->copy()->setTime(0, 0, second: 0, microseconds: 0);
    }

    protected static function getHistoricLocations(Product $product, Warehouse $warehouse): Collection
    {
        [$recentOccupiedLocationIds, $latestDate] = static::getHistoricLocationIdsFromOccupied($product, $warehouse);
        $locationIds = static::getHistoricLocationIdsFromInventory($product, $warehouse, $latestDate)
            ->merge($recentOccupiedLocationIds)
            ->unique();

        return Location::whereIn('id', $locationIds->toArray())->get();
    }

    protected static function getHistoricLocationIdsFromOccupied(Product $product, Warehouse $warehouse): array {
        $latestOccupiedLocationLog = static::getLatestOccupiedLocationLog($product, warehouse: $warehouse);
        $recentOccupiedLocationIds = collect();
        $latestDate = null;

        if (!is_null($latestOccupiedLocationLog)) {
            $latestDate = Carbon::parse($latestOccupiedLocationLog->calendar_date);
            $recentOccupiedLocationIds = static::getOccupiedLocationLogs($product, $warehouse, $latestDate)
                ->pluck('location_id');
        }

        return [$recentOccupiedLocationIds, $latestDate];
    }

    protected static function getHistoricLocationIdsFromInventory(
        Product $product, Warehouse $warehouse, ?Carbon $since = null
    ): Collection
    {
        $historicLocationQuery = $product->inventoryLogs()
            ->whereHas('location', fn (Builder $builder) => $builder->where('warehouse_id', $warehouse->id));

        if (!is_null($since)) {
            $historicLocationQuery = $historicLocationQuery->where('created_at', '>=', $since);
        }

        return $historicLocationQuery->groupBy('location_id')
            ->pluck('location_id');
    }

    protected static function getInventoryLogsQuery(Product $product, Location $location, Carbon $datetime): HasMany
    {
        return $product->inventoryLogs()
            ->where('location_id', $location->id)
            ->where('created_at', '>=', static::getStartDateTime($datetime))
            ->where('created_at', '<', $datetime);
    }

    protected static function getStartDateTime(Carbon $datetime): Carbon
    {
        return $datetime->copy()->subHours(24);
    }

    protected static function logOccupiedLocationFromInventoryLogs(Product $product, Location $location, Carbon $datetime): void
    {
        $getLogsQuery = fn () => static::getInventoryLogsQuery($product, $location, $datetime);
        // TODO: Store with relevant data (inventory log IDs, etc.)
        $occupiedLocationLog = OccupiedLocationLog::buildFromModels($product, $location, static::getStartDateTime($datetime));
        $occupiedLocationLog->inventory = [
            'first' => $getLogsQuery()->orderBy('created_at')->first()->previous_on_hand,
            'max' => max($getLogsQuery()->max('new_on_hand'), $getLogsQuery()->max('previous_on_hand')),
            'last' => $getLogsQuery()->orderByDesc('created_at')->first()->new_on_hand
        ];
        $occupiedLocationLog->save();
    }

    protected static function getLatestOccupiedLocationLog(
        Product $product, ?Warehouse $warehouse = null, ?Location $location = null
    ): ?OccupiedLocationLog
    {
        $criteria = ['product_id' => $product->id];

        if (!is_null($location)) {
            ['location_id' => $location->id];
        } elseif (!is_null($warehouse)) {
            ['warehouse_id' => $warehouse->id];
        }

        return OccupiedLocationLog::where($criteria)->latest()->first();
    }

    protected static function getOccupiedLocationLogs(Product $product, Warehouse $warehouse, Carbon $datetime): Collection
    {
        return OccupiedLocationLog::where([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'calendar_date' => $datetime->toDateString()
        ])
        ->groupBy('location_id')
        ->get(['location_id', 'location']);
    }

    protected static function getLatestInventoryLog(Product $product, Location $location, Carbon $datetime): ?InventoryLog
    {
        // TODO: Restrict with a "since" parameter.
        return $product->inventoryLogs()
            ->where('location_id', $location->id)
            ->whereDate('created_at', '<', $datetime->toDateString())
            ->orderByDesc('created_at')
            ->first();
    }

    public function calculateOccupiedLocationsByDay(
        Customer $client,
        Warehouse $warehouse,
        \Carbon\Carbon $calendarDate
    ): WarehouseOccupiedLocationTypesCacheDocument
    {
        /** @var WarehouseOccupiedLocationTypesCacheDocument $existingOccupations */
        $existingOccupations = WarehouseOccupiedLocationTypesCacheDocument::query()
            ->where('customer_id', $client->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('calendar_date', $calendarDate->toDateString())
            ->first();

        if ($existingOccupations) {
            return $existingOccupations;
        }

        /** @var \Illuminate\Database\Eloquent\Collection $result */
        $result = OccupiedLocationLog::raw(function ($collection) use ($client, $warehouse, $calendarDate) {
            return $collection->aggregate([
                [
                    '$match' => [
                        'calendar_date' => [
                            '$eq' => $calendarDate->toDateString(),
                        ],
                        'product.customer.id' => $client->id,
                        'warehouse_id' => $warehouse->id,
                        'deleted_at' => null,
                    ]
                ],
                [
                    '$group' => [
                        '_id' => [
                            'calendar_date' => '$calendar_date',
                            'location' => '$location.id',
                        ],
                        'location_name' => [
                            '$first' => '$location.name'
                        ],
                        'location_type' => [
                            '$first' => '$location.type'
                        ],
                        'location_type_id' => [
                            '$first' => '$location.type_id'
                        ],
                    ]
                ],
                [
                    '$group' => [
                        '_id' => [
                            'location_type_id' => '$location_type_id',
                            'location_type' => '$location_type',
                        ],
                        'calendar_date' => [
                            '$first' => '$_id.calendar_date'
                        ],
                        'occupied_locations' => [
                            '$push' => [
                                'location_id' => '$_id.location',
                                'location_name' => '$location_name'
                            ]
                        ],
                    ]
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'location_type' => '$_id.location_type',
                        'location_type_id' => '$_id.location_type_id',
                        'calendar_date' => 1,
                        'occupied_locations' => 1
                    ]
                ]
            ]);
        });

        $locations = $result->map(function (OccupiedLocationLog $log) {
            return ['type' => $log['location_type'], 'type_id' => $log['location_type_id'], 'occupied_locations' => $log['occupied_locations']];
        });

        $occupations = WarehouseOccupiedLocationTypesCacheDocument::buildFromModels(
            $client,
            $warehouse,
            $calendarDate,
            $locations->toArray()
        );
        $occupations->save();

        return $occupations;
    }
}
