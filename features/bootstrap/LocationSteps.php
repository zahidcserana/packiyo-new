<?php

use App\Models\BulkShipBatch;
use App\Models\CustomerSetting;
use App\Models\Location;
use App\Models\LocationType;
use App\Models\Order;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;

trait LocationSteps
{
    use HasWarehouseInScope;
    protected Location|null $locationInScope = null;
    protected LocationType|null $locationTypeInScope = null;

    /**
     * @Given the warehouse :warehouseName has a location type called :locationTypeName
     */
    public function theWarehouseHasALocationTypeCalled($warehouseName, $locationTypeName): void
    {
        $warehouse = Warehouse::whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
            $query->where('name', $warehouseName);
        })
            ->firstOrFail();

        $this->setWarehouseInScope($warehouse);

        $this->setCustomerInScope($warehouse->customer);

        $locationTypeAttributes = [
            'customer_id' => $warehouse->customer_id,
            'pickable' => true,
            'sellable' => true,
            'name' => $locationTypeName
        ];

        $locationType = LocationType::where($locationTypeAttributes)->first();

        if (is_null($locationType)) {
            $locationType = LocationType::factory()->create($locationTypeAttributes);

            $locationType->save();

            $locationType = LocationType::where($locationTypeAttributes)->first();

            $this->locationTypeInScope = $locationType;
        }
    }

    /**
     * @Given the location :locationName is of type :locationTypeName
     */
    public function theLocationIsOfType($locationName, $locationTypeName): void
    {
        $location = Location::whereWarehouseId($this->getWarehouseInScope()->id)
            ->where('name', $locationName)
            ->firstOrFail();

        $this->locationInScope = $location;

        if (is_null($this->locationTypeInScope)) {
            $locationType = Location::whereWarehouseId($this->getWarehouseInScope()->id)
                ->where('name', $locationTypeName)
                ->firstOrFail();

            $this->locationTypeInScope = $locationType;
        }

        $location->location_type_id = $this->locationTypeInScope->id;

        $location->save();
    }

    /**
     * @Given the location type :locationTypeName has bulk ship pickable set to :flag
     */
    public function theLocationTypeHasBulkShipPickableSetToTrue($locationTypeName, $flag): void
    {
        $locationType = LocationType::whereCustomerId($this->getWarehouseInScope()->customer_id)
            ->where('name', $locationTypeName)
            ->firstOrFail();

        $this->locationTypeInScope = $locationType;

        $this->locationTypeInScope->bulk_ship_pickable = (int) $flag;
        $this->locationTypeInScope->save();
    }

    /**
     * @Given I should have :numberOfLocations bulk ship pickable locations
     */
    public function iShouldHaveBulkShipPickableLocations($numberOfLocations): void
    {
        $bulkShipBatch = BulkShipBatch::whereCustomerId($this->getWarehouseInScope()->customer_id)
            ->first();

        $firstOrderInBatch = Order::whereBatchKey($bulkShipBatch->batch_key)->firstOrFail();

        $this->assertEquals($firstOrderInBatch->orderItems()->count(), 1);

        $orderItem = $firstOrderInBatch->orderItems()->firstOrFail();

        $onlyUseBulkShipPickableLocations = CustomerSetting::where('customer_id', $this->getWarehouseInScope()->customer_id)
            ->where('key', CustomerSetting::CUSTOMER_SETTING_ONLY_USE_BULK_SHIP_PICKABLE_LOCATIONS)
            ->firstOrFail();

        if ($onlyUseBulkShipPickableLocations->value == "true") {
            $orderItemLocations = $orderItem->product->locations()->where('bulk_ship_pickable_effective', 1)->count();
        } else {
            $orderItemLocations = $orderItem->product->locations()->where('pickable_effective', 1)->count();
        }


        $this->assertEquals((int) $numberOfLocations, $orderItemLocations);
    }

    /**
     * @When the location :locationName has bulk ship pickable set to :flag
     */
    public function theLocationHasBulkShipPickableSetTo($locationName, $flag): void
    {
        if (is_null($this->locationInScope)) {
            $location = Location::whereWarehouseId($this->getWarehouseInScope()->id)
                ->where('name', $locationName)
                ->firstOrFail();

            $this->locationInScope = $location;
        }

        $this->locationInScope->bulk_ship_pickable = (int) $flag;
        $this->locationInScope->save();
    }

    /**
     * @param string $value
     * @param array $location_types
     * @return array
     */
    private function getLocationTypeId(string $value, array $location_types): array
    {
        $locationType = LocationType::whereName($value)->first() ?? null;
        if ($locationType instanceof LocationType) {
            $location_types[] = "$locationType->id";
        }
        return $location_types;
    }

    /**
     * @When the SKU :sku is not assigned to the location :locationName
     */
    public function theSkuIsNotAssignedToTheLocation(string $sku, string $locationName): void
    {
        $location = Location::whereWarehouseId($this->getWarehouseInScope()->id)
            ->where('name', $locationName)
            ->firstOrFail();
        $sku = \App\Models\Product::whereSku($sku)->firstOrFail();

        $location->products()->detach($sku);
    }
}
