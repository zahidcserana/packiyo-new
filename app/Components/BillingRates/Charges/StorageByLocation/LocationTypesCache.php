<?php

namespace App\Components\BillingRates\Charges\StorageByLocation;

use App\Models\LocationType;
use Illuminate\Support\Collection;

class LocationTypesCache
{
    /**
     * @var Collection<int, LocationType>
     */
    private readonly Collection $cache;

    public function __construct()
    {
        $this->cache = collect();
    }

    public function get(int $locationTypeId): ?LocationType
    {
        $foundInCache = $this->cache->get($locationTypeId);

        if ($foundInCache) {
            return $foundInCache;
        }

        $locationType = LocationType::query()
            ->find($locationTypeId,
                ['id', 'name', 'sellable', 'pickable', 'bulk_ship_pickable', 'disabled_on_picking_app']);

        if ($locationType) {
            $this->add($locationType);
        }

        return $locationType;
    }

    public function add(LocationType $locationType): void
    {
        $this->cache->put($locationType->id, $locationType);
    }
}
