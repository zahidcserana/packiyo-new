<?php

use App\Models\{CacheDocuments\WarehouseOccupiedLocationTypesCacheDocument, Customer, Location, Product};
use Behat\Gherkin\Node\TableNode;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Behat steps to test customers.
 */
trait InventorySteps
{
    use \App\Components\CalculatesOccupiedLocations;

    /**
     * @When I calculate the locations occupied by the client :customerName on the date :date
     */
    public function iCalculateTheLocationsOccupiedByTheClientOnTheDate(string $customerName, string $date): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        App::make('inventoryLog')->calculateLocationsOccupiedByCustomer(
            $customer, $customer->parent->warehouses->first(), Carbon::parse($date)
        );
    }

    /**
     * @When I calculate the locations occupied by the client :customerName on the date :date for all clients
     */
    public function iCalculateTheLocationsOccupiedByTheClientOnTheDateForAllClients(string $customerName, string $date): void
    {
        $this->travelTo(Carbon::parse($date)->startOfDay());
        $this->calculateOccupiedLocations();
    }

    /**
     * @When I calculate the locations occupied by all clients from :startDate to :endDate
     */
    public function iCalculateTheLocationsOccupiedByAllClientsFromTo(string $startDate, string $endDate): void
    {
        // Simulate the Schedule call
        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            $this->travelTo($date->startOfDay());
            Artisan::call('calculate:occupied-locations');
        }
    }

    /**
     * @Then the client :customerName should have :quantity warehouse occupied location types
     */
    public function theClientShouldHaveWarehouseOccupiedLocationTypes(string $customerName, int $quantity): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $aggregations = WarehouseOccupiedLocationTypesCacheDocument::query()
            ->where('customer_id', $customer->id)
            ->get();

        $this->assertCount($quantity, $aggregations);
    }

    /**
     * @Then the client :customerName should have a warehouse aggregation for the warehouse :warehouseName on date :date with:
     */
    public function theClientShouldHaveAWarehouseForTheWarehouseAggregationOnDate(string $customerName, string $warehouseName, string $date, TableNode $table): void
    {
        $calendarDate = Carbon::parse($date)->toDateString();

        $aggregation = WarehouseOccupiedLocationTypesCacheDocument::query()
            ->where('calendar_date', $calendarDate)
            ->where('customer.name', $customerName)
            ->where('warehouse.name', $warehouseName)
            ->firstOrFail();

        $expected = $table->getHash();

        if (count($expected) === 0) {
            $this->assertEmpty($aggregation->locations);
        } else {
            foreach ($expected as $row) {
                $occupiedTypes = collect($aggregation->location_types)->where('type', $row['type'])->first();
                $this->assertNotNull($occupiedTypes);

                $occupations = collect($occupiedTypes['occupied_locations'])->pluck('location_name')->toArray();
                $this->assertContains($row['location'], $occupations);
            }
        }
    }

    /**
     * @Given the customer :customerName had :quantity units of :sku in :locationName on :date
     * @throws Exception
     */
    public function theCustomerHadUnitsOfInOn(string $customerName, int $quantity, string $sku, string $locationName, string $date): void
    {
        $product = Product::query()
            ->where('sku', $sku)
            ->firstOrFail();

        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        if ($product->customer_id !== $customer->id) {
            throw new Exception('The product does not belong to the customer.');
        }

        $location = Location::query()
            ->where('name', $locationName)
            ->firstOrFail();

        \App\Models\OccupiedLocationLog::query()
            ->create([
                'product_id' => $product->id,
                'location_id' => $location->id,
                'warehouse_id' => $location->warehouse_id,
                'calendar_date' => Carbon::parse($date)->toDateString(),
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
                'location' => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'type' => $location->locationType ? $location->locationType->name : null,
                    'type_id' => $location->locationType ? $location->locationType->id : null,
                ],
                'warehouse' => [
                    'id' => $location->warehouse_id,
                    'name' => $location->warehouse->contactInformation->name,
                    'country' => $location->warehouse->contactInformation->country->iso_3166_2,
                    'state' => $location->warehouse->contactInformation->state,
                    'city' => $location->warehouse->contactInformation->city
                ],
                'timezone' => Carbon::parse($date)->timezone->getName(),
                'inventory' => [
                    'first' => $quantity,
                    'max' => $quantity,
                    'last' => $quantity
                ]
            ]);
    }
}
