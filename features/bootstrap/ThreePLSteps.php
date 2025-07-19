<?php

use App\Components\PackingComponent;
use App\Components\Shipping\Providers\GenericShippingProvider;
use App\Components\ShippingComponent;
use App\Events\OrderShippedEvent;
use App\Http\Requests\Packing\StoreRequest as PackingStoreRequest;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use App\Models\{
    BillingBalance,
    CacheDocuments\StorageByLocationChargeCacheDocument,
    CacheDocuments\WarehouseOccupiedLocationTypesCacheDocument,
    Customer,
    OrderStatus,
    Package,
    PackageOrderItem,
    EasypostCredential,
    Warehouse,
    ContactInformation,
    InventoryLog,
    LocationType,
    Location,
    LocationProduct,
    Product,
    Order,
    OrderItem,
    ShippingMethod,
    Shipment,
    ShipmentTracking,
    ShippingBox,
    ShippingCarrier,
    Tote
};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class GenericShippingProviderMock extends GenericShippingProvider
{
    public ShippingMethod $shippingMethod;
    public string $trackingNumber;
    public ?string $cost;
    public array $shipmentAssigment = [];
    public function __construct(ShippingMethod $shippingMethod = null, ?string $trackingNumber = null, ?string $cost = null)
    {
        $this->shippingMethod = $shippingMethod;
        $this->trackingNumber = $trackingNumber;
        $this->cost = $cost;
    }

    public function ship(Order $order, $storeRequest, ShippingMethod $shippingMethod = null): array
    {
        $result =  parent::ship($order, $storeRequest);

        $this->addsTrackingNumber($result[0]);
        $result[0]->shipping_method_id = $this->shippingMethod->id ?? null;
        $result[0]->cost = floatval($this->cost) ?? null;
        $result[0]->save();
        return $result;
    }

    public function addsTrackingNumber(Shipment $shipment): void
    {

        if ($shipment->shipmentTrackings->isNotEmpty()) {
            $shipmentTracking = $shipment->shipmentTrackings->first();
            $shipmentTracking->tracking_number = $this->trackingNumber;
            $shipmentTracking->save();
        } else {
            ShipmentTracking::factory()->create([
                'shipment_id' => $shipment->id,
                'tracking_number' => $this->trackingNumber
            ]);
            $shipment->refresh();
        }
    }
}
/**
 * Behat steps to test 3PLs and 3PL clients.
 */
trait ThreePLSteps
{
    /**
     * @Given a 3PL called :threePLName based in :countryName
     */
    public function a3PlCalledBasedIn(string $threePLName, string $countryName): void
    {
        $country = \Countries::where('name', $countryName)->firstOrFail();
        $customer = Customer::factory()->create(['allow_child_customers' => true]);
        ContactInformation::factory()->create([
            'object_type' => Customer::class,
            'object_id' => $customer->id,
            'country_id' => $country->id,
            'name' => $threePLName
        ]);

        if (
            method_exists($this, 'hasCustomerInScope')
            && !$this->hasCustomerInScope()
            && method_exists($this, 'setCustomerInScope')
        ) {
            $this->setCustomerInScope($customer);
        }
    }

    /**
     * @Given a 3PL called :customerName was created on :date
     */
    public function aPlCalledWasCreatedOn($customerName, $date)
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $customer->created_at = new Carbon($date);
        $customer->save();
    }

    /**
     * @Given the customer :customerName has a warehouse named :warehouseName in :countryName
     */
    public function theCustomerHasAWarehouseNamedIn(string $customerName, string $warehouseName, string $countryName): void
    {
        $country = \Countries::where('name', $countryName)->firstOrFail();
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $warehouse = Warehouse::factory()->create(['customer_id' => $customer->id]);
        ContactInformation::factory()->create([
            'object_type' => Warehouse::class,
            'object_id' => $warehouse->id,
            'country_id' => $country->id,
            'name' => $warehouseName
        ]);

        if (method_exists($this, 'setWarehouseInScope')) {
            $this->setWarehouseInScope($warehouse);
        }
    }

    /**
     * @Given the warehouse address is
     */
    public function theWarehouseAddressIs(TableNode $addressData): void
    {
        $warehouse = $this->getWarehouseInScope();
        $warehouseName = $warehouse->contactInformation->name;

        $warehouse->contactInformation()->forceDelete();

        $address = $addressData->getHash()[0];
        ContactInformation::factory()->create([
            'name' => $warehouseName,
            'object_type' => Warehouse::class,
            'object_id' => $warehouse->id,
            'country_id' => $address['country_id'] ?? null,
            'address' => $address['address'] ?? null,
            'city' => $address['city'] ?? null,
            'state' => $address['state'] ?? null,
            'zip' => $address['zip'] ?? null,
            'phone' => $address['phone'] ?? null,
            'email' => $address['email'] ?? null
        ]);

        $this->setWarehouseInScope($warehouse->refresh());
    }

    /**
     * @Given the warehouse :warehouseName has :locationCount locations of type :locationTypeName
     */
    public function theWarehouseHasLocationsOfType(string $warehouseName, int $locationCount, string $locationTypeName): void
    {
        $warehouse = Warehouse::whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
            $query->where('name', $warehouseName);
        })->firstOrFail();
        $locationTypeAttributes = [
            'customer_id' => $warehouse->customer_id,
            'pickable' => true,
            'sellable' => true,
            'name' => $locationTypeName
        ];
        $locationType = LocationType::where($locationTypeAttributes)->first();

        if (is_null($locationType)) {
            $locationType = LocationType::factory()->create($locationTypeAttributes);
        }

        Location::factory()->count($locationCount)->create([
            'warehouse_id' => $warehouse->id,
            'location_type_id' => $locationType->id,
            'pickable' => $locationType->pickable,
            'sellable' => $locationType->sellable
        ]);
    }

    /**
     * @Given the warehouse :warehouseName has :locationCount generic locations
     */
    public function theWarehouseHasGenericLocations($warehouseName, $locationCount)
    {
        $warehouse = Warehouse::whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
            $query->where('name', $warehouseName);
        })->firstOrFail();

        $location = Location::factory()->count($locationCount)->create([
            'warehouse_id' => $warehouse->id,
            'location_type_id' => null,
            'name' => null,
            'pickable' => true,
            'sellable' => true
        ]);
    }

    /**
     * @Given the warehouse :warehouseName has :toteCount totes prefixed :prefix
     */
    public function theWarehouseHasTotesPrefixed(string $warehouseName, int $locationCount, string $prefix): void
    {
        $warehouse = Warehouse::whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
            $query->where('name', $warehouseName);
        })->firstOrFail();

        for ($i = 0; $i < $locationCount; $i++) {
            $toteName = $locationCount === 1 ? $prefix : Tote::getUniqueIdentifier($prefix, $warehouse->id);

            Tote::create([
                'warehouse_id' => $warehouse->id,
                'name' => $toteName,
                'barcode' => $toteName
            ]);
        }
    }

    /**
     * @Given the warehouse :warehouseName has :quantity SKU :sku in location :locationName
     */
    public function theWarehouseHadSkuInLocationFrom(
        string $warehouseName, int $quantity, string $sku, string $locationName
    ): void
    {
        $warehouse = Warehouse::whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
            $query->where('name', $warehouseName);
        })->firstOrFail();
        $location = Location::where(['warehouse_id' => $warehouse->id, 'name' => $locationName])->firstOrFail();
        $product = Product::where(['sku' => $sku])->firstOrFail(); // TODO: Filter by 3PL client too.
        // TODO: This shouldn't even be checked - it could not exist when the invoice is calculated.
        $product->locations()->attach($location->id, ['quantity_on_hand' => $quantity]);
        $product->save();

        LocationProduct::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity_on_hand' => $quantity
        ]);
    }

    /**
     * @Given we log all occupied locations in the warehouse :warehouseName for the customer :customerName from :startDate to :endDate
     */
    public function weLogAllOccupiedLocations(string $warehouseName, string $customerName, string $startDate, string $endDate): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $warehouse = Warehouse::whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
            $query->where('name', $warehouseName);
        })->firstOrFail();

        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate)->addDay(); // add a new day to include all dates in intervals

        foreach ($startDate->toPeriod($endDate, '1 day') as $date) {
            App::make('inventoryLog')->calculateLocationsOccupiedByCustomer(
                $customer, $warehouse, \Illuminate\Support\Carbon::createFromInterface($date)
            );
        }
    }

    /**
     * @Given the warehouse :warehouseName had :quantity SKU :sku in location :locationName from :startDate to :endDate
     */
    public function theWarehouseHadSkuInLocationFromTo(
        string $warehouseName, int $quantity, string $sku, string $locationName, string $startDate, string $endDate
    ): void
    {
        $warehouse = Warehouse::whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
            $query->where('name', $warehouseName);
        })->firstOrFail();
        $location = Location::where(['warehouse_id' => $warehouse->id, 'name' => $locationName])->firstOrFail();
        $product = Product::where(['sku' => $sku])->firstOrFail(); // TODO: Filter by 3PL client too.
        // TODO: This shouldn't even be checked - it could not exist when the invoice is calculated.
        $product->locations()->attach($location->id, ['quantity_on_hand' => $quantity]);
        $product->save();

        $startDate = Carbon::parse($startDate)->addHours(8); // 8am
        $endDate = Carbon::parse($endDate)->addHours(12); // noon

        // Start storing product.
        InventoryLog::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'previous_on_hand' => 0,
            'new_on_hand' => $quantity,
            'quantity' => $quantity,
            'created_at' => $startDate,
            'updated_at' => $startDate
        ]);
        // Stop storing product.
        InventoryLog::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'previous_on_hand' => $quantity,
            'new_on_hand' => 0,
            'quantity' => -$quantity,
            'created_at' => $endDate,
            'updated_at' => $endDate
        ]);
    }

    /**
     * @Given the warehouse :warehouseName had :quantity SKU :sku in no-location from :startDate to :endDate
     */
    public function theWarehouseHadSkuInNoLocationFromTo(string $warehouseName, int $quantity, string $sku, string $startDate, string $endDate
    )
    {
        $warehouse = Warehouse::whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
            $query->where('name', $warehouseName);
        })->firstOrFail();
        $location = Location::where(['warehouse_id' => $warehouse->id, 'location_type_id' => null])->firstOrFail();
        $product = Product::where(['sku' => $sku])->firstOrFail(); // TODO: Filter by 3PL client too.
        // TODO: This shouldn't even be checked - it could not exist when the invoice is calculated.
        $product->locations()->attach($location->id, ['quantity_on_hand' => $quantity]);
        $product->save();

        $startDate = Carbon::parse($startDate)->addHours(8); // 8am
        $endDate = Carbon::parse($endDate)->addHours(12); // noon

        // Start storing product.
        InventoryLog::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'previous_on_hand' => 0,
            'new_on_hand' => $quantity,
            'quantity' => $quantity,
            'created_at' => $startDate,
            'updated_at' => $startDate
        ]);
        // Stop storing product.
        InventoryLog::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'previous_on_hand' => $quantity,
            'new_on_hand' => 0,
            'quantity' => -$quantity,
            'created_at' => $endDate,
            'updated_at' => $endDate
        ]);
    }

    /**
     * @Given we lost all the warehouse cache documents for the warehouse :warehouseName
     */
    public function weLostTheWarehouseCacheDocumentsForTheWarehouse($warehouseName)
    {
        $warehouse = Warehouse::whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
            $query->where('name', $warehouseName);
        })->firstOrFail();

        $docs = WarehouseOccupiedLocationTypesCacheDocument::where($warehouse->id)->get();
        $docs->each->delete();
    }

    /**
     * @Given we lost :documentCount warehouse cache documents for the warehouse :warehouseName
     */
    public function weLostDocumentCountTheWarehouseCacheDocumentsForTheWarehouse($documentCount, $warehouseName)
    {
        $warehouse = Warehouse::whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
            $query->where('name', $warehouseName);
        })->firstOrFail();

        $docs = WarehouseOccupiedLocationTypesCacheDocument::where($warehouse->id)->get();

        $randomElements = $docs->random($documentCount);
        $randomElements->each->delete();
    }

    /**
     * @description simulate the scenario were we could lose at least X amount of documents at random
     * @When we lost at most :documentCount storage by location cache documents
     */
    public function weLostRandomStorageByLocationCacheDocuments($documentCount)
    {
        $ids = StorageByLocationChargeCacheDocument::all()->pluck('id')->toArray();
        shuffle($ids);

        $randomCount = rand(1, min($documentCount, count($ids)));
        $randomIds = array_slice($ids, 0, $randomCount);
        Log::debug("Remove $randomCount cache documents");
        $docs = StorageByLocationChargeCacheDocument::whereIn('_id', $randomIds)->get();
        $docs->each->delete();
    }

    /**
     * @When we lost at most :documentCount warehouse by location cache documents
     */
    public function weLostAtMostWarehouseByLocationCacheDocuments($documentCount)
    {
        $ids = WarehouseOccupiedLocationTypesCacheDocument::all()->pluck('id')->toArray();
        shuffle($ids);

        $randomCount = rand(1, min($documentCount, count($ids)));
        $randomIds = array_slice($ids, 0, $randomCount);
        Log::debug("Remove $randomCount cache documents");
        $docs = WarehouseOccupiedLocationTypesCacheDocument::whereIn('_id', $randomIds)->get();
        $docs->each->delete();
    }


    /**
     * @Then :documentCount storage cache where deleted during process
     */
    public function storageCacheWhereDeletedDuringProcess($documentCount)
    {
        $trashedRecords = StorageByLocationChargeCacheDocument::onlyTrashed()->get();
        $this->assertCount($documentCount, $trashedRecords);
    }


    /**
     * @When we lost storage by location cache documents for :warehouseName
     */
    public function weLostStorageByLocationCacheDocumentsFor($warehouseName)
    {
        $warehouse = Warehouse::whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
            $query->where('name', $warehouseName);
        })->firstOrFail();

        $docs = StorageByLocationChargeCacheDocument::where('warehouse_id', $warehouse->id)->get();
        $docs->each->delete();
    }



    /**
     * @Given a shipping carrier :carrierName and a shipping method :methodName
     */
    public function aShippingCarrierAndAShippingMethod(string $carrierName, string $methodName): void
    {
        $carrier = ShippingCarrier::factory()->create(['name' => $carrierName, 'customer_id'=> $this->getCustomerInScope()->id]);
        ShippingMethod::factory()->create(['name' => $methodName, 'shipping_carrier_id' => $carrier->id]);
    }

    /**
     * @Given the shipping carrier :carrierName has the settings
     */
    public function theShippingCarrierHasTheSettings(string $carrierName, TableNode $table): void
    {
        $carrier = ShippingCarrier::where('name', $carrierName)->firstOrFail();
        $settings = $table->getHash()[0];
        $carrier->settings = $settings;
        $carrier->save();
    }

    /**
     * @Given the shipping method :methodName from :carrierName has the settings
     */
    public function theShippingMethodFromHasTheSettings(string $methodName, string $carrierName, TableNode $table): void
    {
        $shippingMethod = ShippingMethod::whereHas('shippingCarrier', function (Builder $query) use (&$carrierName) {
            $query->where('name', $carrierName);
        })->where('name', $methodName)->firstOrFail();

        $settings = $table->getHash()[0];

        $shippingMethod->settings = $settings;

        $shippingMethod->save();
    }

    /**
     * @Given the shipping carrier :carrierName has Easypost credentials
     */
    public function theShippingCarrierHasEasypostCredentials(string $carrierName): void
    {
        $carrier = ShippingCarrier::where('name', $carrierName)->firstOrFail();
        $credentials = EasypostCredential::withoutEvents(fn () =>
            EasypostCredential::create(['customer_id' => $carrier->customer_id])
        );
        $carrier->credential_id = $credentials->id;
        $carrier->save();
    }

    /**
     * @Given the shipping carrier :carrierName has Tribird credentials
     */
    public function theShippingCarrierHasTribirdCredentials(string $carrierName): void
    {
        $carrier = ShippingCarrier::where('name', $carrierName)->firstOrFail();
        $credentials = \App\Models\TribirdCredential::withoutEvents(fn () =>
            \App\Models\TribirdCredential::create(['customer_id' => $carrier->customer_id, 'settings' => []])
        );
        $carrier->credential_id = $credentials->id;
        $carrier->save();
    }

    /**
     * @Given the shipping carrier :carrierName has Webshipper credentials
     */
    public function theShippingCarrierHasWebshipperCredentials(string $carrierName): void
    {
        $carrier = ShippingCarrier::where('name', $carrierName)->firstOrFail();
        $credentials = \App\Models\WebshipperCredential::withoutEvents(fn () =>
            \App\Models\WebshipperCredential::create(['customer_id' => $carrier->customer_id])
        );
        $carrier->credential_id = $credentials->id;
        $carrier->save();
    }


    /**
     * @Given a shipping carrier :carrierName and a shipping method :methodName for :customerName
     */
    public function aShippingCarrierAndAShippingMethodFor(string $carrierName, string $methodName, string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $carrier = ShippingCarrier::factory()->create(['name' => $carrierName, 'customer_id'=>$customer->id]);
        ShippingMethod::factory()->create(['name' => $methodName, 'shipping_carrier_id' => $carrier->id]);
    }

    /**
     * @Given a shipping carrier :carrierName contains shipping method :methodName
     */
    public function aShippingCarrierContainsShippingMethod(string $carrierName, string $methodName): void
    {
        $customerId = $this->getCustomerInScope()->id;
        $carrier = ShippingCarrier::where('name', $carrierName)
            ->where( 'customer_id' , $customerId)
            ->first();
        ShippingMethod::factory()->create(['name' => $methodName, 'shipping_carrier_id' => $carrier->id]);
    }

    /**
     * @Given for a customer :customerName a shipping carrier :carrierName and a shipping method :methodName
     */
    public function forACustomerAShippingCarrierAndAShippingMethod(string $customerName, string $carrierName, string $methodName): void
    {
        $customerId = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail()->id;
        $carrier = ShippingCarrier::factory()->create(['name' => $carrierName, 'customer_id' => $customerId]);
        ShippingMethod::factory()->create(['name' => $methodName, 'shipping_carrier_id' => $carrier->id]);
    }

    /**
     * @Given a customer called :customerName based in :countryName client of 3PL :threePLName
     */
    public function aCustomerCalledBasedInClientOfPl(string $customerName, string $countryName, string $threePLName): void
    {
        $country = \Countries::where('name', $countryName)->firstOrFail();
        $threePL = Customer::whereHas('contactInformation', function (Builder $query) use (&$threePLName) {
            $query->where('name', $threePLName);
        })->firstOrFail();
        $customer = Customer::factory()->create(['parent_id' => $threePL->id]);
        ContactInformation::factory()->create([
            'object_type' => Customer::class,
            'object_id' => $customer->id,
            'country_id' => $country->id,
            'name' => $customerName
        ]);
    }

    /**
     * @Given remove customer :customerName
     */
    public function removeCustomer($customerName)
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $customer->delete();
    }

    /**
     * @Given the 3PL :threePLName shipped order :orderNumber for its client :customerName through :carrierName on the :shippedDate
     */
    public function thePlShippedOrderForItsClientThroughOnThe(
        string $threePLName,
        string $customerName,
        string $orderNumber,
        string $carrierName,
        string $shippedDate
    ): void
    {
        // 3PL is not identified.
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $shippingMethod = ShippingMethod::whereHas('shippingCarrier', function (Builder $query) use (&$carrierName) {
            $query->where('name', $carrierName);
        })->firstOrFail();
        $order = Order::where(['customer_id' => $customer->id, 'number' => $orderNumber])->firstOrFail();
        Shipment::factory()->create([
            // 'user_id' => $user->id,  // Identifies shipping 3PL.
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'created_at' => $shippedDate,
            'updated_at' => $shippedDate
        ]);
    }

    /**
     * @Given the 3PL :threePLName shipped order :orderNumber for its client :customerName through :carrierName with method :methodName on the :shippedDate
     */
    public function thePlShippedOrderForItsClientThroughWithMethodOnThe(
        string $threePLName,
        string $customerName,
        string $orderNumber,
        string $carrierName,
        string $methodName,
        string $shippedDate
    ): void
    {
        // 3PL is not identified.
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $shippingMethod = ShippingMethod::whereHas('shippingCarrier', function (Builder $query) use (&$carrierName) {
            $query->where('name', $carrierName);
        })->where('name', $methodName)->firstOrFail();
        $order = Order::where(['customer_id' => $customer->id, 'number' => $orderNumber])->firstOrFail();
        Shipment::factory()->create([
            // 'user_id' => $user->id,  // Identifies shipping 3PL.
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'created_at' => $shippedDate,
            'updated_at' => $shippedDate
        ]);
    }

    /**
     * @Given the 3PL :threeplName shipped order :orderNumber for its client :customerName through :carrierName on the :shippedDate with tracking :trackingNumber
     */
    public function thePlShippedOrderForItsClientThroughOnTheWithTracking(
        string $threeplName,
        string $customerName,
        string $orderNumber,
        string $carrierName,
        string $shippedDate,
        string $trackingNumber
    ): void
    {
        // 3PL is not identified.
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
                $query->where('name', $customerName);
            })->firstOrFail();
        $shippingMethod = ShippingMethod::whereHas('shippingCarrier', function (Builder $query) use (&$carrierName) {
            $query->where('name', $carrierName);
        })->firstOrFail();
        $order = Order::where(['customer_id' => $customer->id, 'number' => $orderNumber])->firstOrFail();
        $shipment = Shipment::factory()->create([
            'user_id' => $this->getUserInScope()->id,
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'created_at' => $shippedDate,
            'updated_at' => $shippedDate
        ]);
        ShipmentTracking::factory()->create([
            'shipment_id' => $shipment->id,
            'tracking_number' => $trackingNumber
        ]);
        $shipment->refresh();
    }

    /**
     * @Given the client :customerName has a balance of :amount
     */
    public function theClientHasABalanceOf(string $customerName, string $amount): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        BillingBalance::factory()->create([
            'threepl_id' => $customer->parent->id,
            'warehouse_id' => $customer->parent->warehouses[0]->id,
            'client_id' => $customer->id,
            'amount' => $amount
        ]);
    }

    /**
     * @When the 3PL :threeplName ships order :orderNumber for its client :customerName through :carrierName on the :shippedDate
     */
    public function thePlShipsOrderForItsClientThroughOnThe(
        string $threeplName,
        string $customerName,
        string $orderNumber,
        string $carrierName,
        string $shippedDate
    ): Shipment
    {
        return $this->thePlShipsOrderForItsClientThroughOnTheWithMock(
            $threeplName,
            $customerName,
            $orderNumber,
            $carrierName,
            $shippedDate,
        );
    }
    public function thePlShipsOrderForItsClientThroughOnTheWithMock(
        string $threeplName,
        string $customerName,
        string $orderNumber,
        string $carrierName,
        string $shippedDate,
        bool $withMock = false,
        ?string $trackingNumber = null,
        ?string $cost = null
    ): Shipment
    {
        // 3PL is not identified.
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $shippingMethod = ShippingMethod::whereHas('shippingCarrier', function (Builder $query) use (&$carrierName) {
            $query->where('name', $carrierName);
        })->firstOrFail();

        if($withMock){
            $this->mockShippingProviderByShippingMethod($shippingMethod, $trackingNumber, $cost);
        }else{
            $shippingMethod->shippingCarrier->carrier_service = null;
            $shippingMethod->shippingCarrier->save();
        }

        #todo think this better
        if(!empty($customer->parent)){
            $order = Order::where(['customer_id' => $customer->id, 'number' => $orderNumber])->firstOrFail();
            $location = Location::where(['warehouse_id' => $customer->parent->warehouses->first()->id])->firstOrFail();
            $box = ShippingBox::where(['customer_id' => $customer->parent->id])->firstOrFail();
        }else{
            $order = Order::where(['customer_id' => $customer->id, 'number' => $orderNumber])->firstOrFail();
            $location = Location::where(['warehouse_id' => $customer->warehouses->first()->id])->firstOrFail();
            $box = ShippingBox::where(['customer_id' => $customer->id])->firstOrFail();
        }
        // Required for EasyPost - but we're forcing generic.
        // $order->currency()->associate(Currency::factory()->create(['code' => 'USD']));
        // $order->save();

        $items = collect();

        foreach ($order->orderItems as $item) {
            if ($item->kitOrderItems->isNotEmpty()) {
                foreach ($item->kitOrderItems as $componentItem) {
                    $componentItem->product->locations()->attach($location->id, ['quantity_on_hand' => $item->quantity * $componentItem->quantity]);
                    $componentItem->product->save();
                    $items->add($componentItem);
                }
            } else if (! $item->parentOrderItem) {
                $item->product->locations()->attach($location->id, ['quantity_on_hand' => $item->quantity]);
                $item->product->save();
                $items->add($item);
            }
        }

        $requestPackingStateItems = $items
            ->map(function (OrderItem $item) use ($location) {
                $state = [];

                $iterations = $item->quantity;

                for ($i = 0; $i < $iterations; $i++) {
                    $state[] = [
                        'orderItem' => (string) $item->id,
                        'location' => (string) $location->id,
                        'tote' => 0,
                        'serialNumber' => '',
                        'parentId' => $item->parentOrderItem ?  $item->parentOrderItem->id : null,
                        // 'packedParentKey' => '_1694783647715'
                    ];
                }

                return $state;
            })->collapse();
        $requestOrderItems = $items->mapWithKeys(fn (OrderItem $item) => [
            // '0_0_136710_1694783647715__3_0_1' => [ // What is this?
            (string) $item->id => [ // What is this?
                'quantity' => $item->quantity,
                'order_item_id' => $item->id,
                'location_id' => $location->id,
                'tote_id' => null,
                // 'kit_count' => null, // Is this used anywhere?
                // 'quantity_in_kit' => null // Ditto.
            ]
        ]);

        // TODO: Unify the two redundant steps!
        $request = PackingStoreRequest::make([
            'packing_state' => json_encode([[
                'items' => $requestPackingStateItems,
                'weight' => 0.705,
                'box' => (string) $box->id,
                '_length' => 6,
                'width' => 6,
                'height' => 6
            ]]),
            'customer_id' => $customer->id,
            'shipping_method_id' => $shippingMethod->id,
            // 'drop_point_id' => null,
            'length' => 6,
            'width' => 6,
            'height' => 6,
            'shipping_box' => 2,
            'weight' => 0.705,
            'order_items' => $requestOrderItems,
            // 'print_packing_slip' => null,
            'shipping_contact_information' => ContactInformation::factory()->create()->toArray(),
            // 'serial_number' => null
        ]);

        [$shipment] = app(PackingComponent::class)->packAndShip($order, $request);
        $shipment->created_at = $shippedDate;
        $shipment->updated_at = $shippedDate;
        $shipment->shipping_method_id = $shippingMethod->id;
        $shipment->save();

        return $shipment;
    }

    /**
     * @When the shipment for order :orderNumber cost is :cost
     */
    public function theShipmentForOrderCostIs($orderNumber, $cost)
    {
        $order = Order::where(['number' => $orderNumber])->firstOrFail();
        $shipments = Shipment::where(['order_id' => $order->id])->get();
        foreach ($shipments as $shipment) {
            $shipment->cost = $cost;
            $shipment->save();
        }
    }


    /**
     * @When the 3PL :threeplName ships order :orderNumber for its client :customerName through :carrierName on the :shippedDate with tracking :trackingNumber
     */
    public function thePlShipsOrderForItsClientThroughOnTheWithTracking(
        string $threeplName,
        string $customerName,
        string $orderNumber,
        string $carrierName,
        string $shippedDate,
        string $trackingNumber
    ): void
    {
        $shipment = $this->thePlShipsOrderForItsClientThroughOnThe(
            $threeplName, $customerName, $orderNumber, $carrierName, $shippedDate, $trackingNumber
        );

        if ($shipment->shipmentTrackings->isNotEmpty()) {
            $shipmentTracking = $shipment->shipmentTrackings->first();
            $shipmentTracking->tracking_number = $trackingNumber;
            $shipmentTracking->save();
        } else {
            ShipmentTracking::factory()->create([
                'shipment_id' => $shipment->id,
                'tracking_number' => $trackingNumber
            ]);
            $shipment->refresh();
        }
    }

    /**
     * @When the 3PL :threeplName ships order :orderNumber for client :customerName through :carrierName on the :shippedDate with tracking :trackingNumber
     */
    public function thePlShipsOrderForClientThroughOnTheWithTracking(
        string $threeplName,
        string $customerName,
        string $orderNumber,
        string $carrierName,
        string $shippedDate,
        string $trackingNumber
    ): void
    {
        $shipment = $this->thePlShipsOrderForItsClientThroughOnTheWithMock(
            $threeplName,
            $customerName,
            $orderNumber,
            $carrierName,
            $shippedDate,
            true,
            $trackingNumber
        );
    }

    /**
     * @When the 3PL :threeplName ships order :orderNumber for client :customerName through :carrierName on the :shippedDate with tracking :trackingNumber and cost :cost
     */
    public function thePlShipsOrderForClientThroughOnTheWithTrackingAndCost(
        string $threeplName,
        string $customerName,
        string $orderNumber,
        string $carrierName,
        string $shippedDate,
        string $trackingNumber,
        string $cost
    ): void
    {
        $shipment = $this->thePlShipsOrderForItsClientThroughOnTheWithMock(
            $threeplName,
            $customerName,
            $orderNumber,
            $carrierName,
            $shippedDate,
            true,
            $trackingNumber,
            $cost
        );
    }

    /**
     * @When the 3PL :threeplName ships order :orderNumber for client :customerName through :carrierName on the :shippedDate with tracking :trackingNumber the order shipped event is dispatch
     */
    public function thePlShipsOrderForClientThroughOnTheWithTrackingTherOrderShippedEventIsDispatch(
        string $threeplName,
        string $customerName,
        string $orderNumber,
        string $carrierName,
        string $shippedDate,
        string $trackingNumber
    ): void
    {

        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $order = Order::where(['customer_id' => $customer->id, 'number' => $orderNumber])->firstOrFail();
        $customer3pl = Customer::whereHas('contactInformation', function (Builder $query) use (&$threeplName) {
            $query->where('name', $threeplName);
        })->firstOrFail(); // need shipping method from 3pl

        $shippingMethod = ShippingMethod::whereHas('shippingCarrier', function (Builder $query) use (&$customer3pl, &$carrierName) {
            $query->where(['customer_id' => $customer3pl->id, 'name' => $carrierName]);
        })->first(); // Could be generic.

        foreach ($this->packages as $shipment => $packages){
            $orderId = $order->id;
            if(!empty($this->shipmentAssigment)){
                $shipmentNumbers = array_keys($this->shipmentAssigment);
                foreach ($shipmentNumbers as $number){
                    if($shipment == $number){
                        $orderId  = $this->shipmentAssigment[$number];
                    }
                }
            }
            $this->shipment[$shipment] = Shipment::factory()
                ->withPackages(array_values($packages))
                ->create([
                    'order_id' => $orderId,
                    'shipping_method_id' => $shippingMethod ? $shippingMethod->id : null,
                    'is_freight' => false,
                    'created_at' => $shippedDate,
                    'updated_at' => $shippedDate
                ]);
        }

        event(new OrderShippedEvent($order, ...$this->shipment));
    }

    /**
     * @Given the order number :orderNumber for 3pl client :customerName was packed as follows
     */
    public function theOrderNumberfor3plClientWasPackedAsFollows(string $orderNumber, string $customerName,TableNode $packagesTable): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $order = Order::where(['customer_id' => $customer->id, 'number' => $orderNumber])->firstOrFail();
        $packages = [];

        foreach ($packagesTable->getRows() as $package) {
            [$number, $boxName, $quantity, $sku, $shipmentNumber] = $package;
            $box = ShippingBox::where(['customer_id' => $customer->id, 'name' => $boxName])->firstOrFail(); //probably could be set for 3pl

            if (empty($packages[$shipmentNumber][$number])) {
                $packages[$shipmentNumber][$number] = Package::factory()->create([
                    'order_id' => $order->id,
                    'shipping_box_id' => $box->id
                ]);
            }

            $product = Product::where(['customer_id' => $customer->id, 'sku' => $sku])->firstOrFail();
            $orderItem = $order->orderItems->first(fn (OrderItem $item) => $item->sku == $product->sku);
            $packages[$shipmentNumber][$number]->packageOrderItems->push(PackageOrderItem::factory()->create([
                'order_item_id' => $orderItem->id,
                'package_id' => $packages[$shipmentNumber][$number]->id,
                'quantity' => $quantity
            ]));
        }

        $this->packages = $packages;
    }

    /**
     * @Given the shipment number :shipmentNumber was assign to order number :orderNumber for 3pl :customerName
     */
    public function thePackageWasAssignToOrderNumber($shipmentNumber, $orderNumber, $customerName)
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $exists = Order::where([
            'number' => $orderNumber,
            'customer_id' => $customer->id
        ])->exists();
        if(!$exists){
            $orderStatus = OrderStatus::factory()->create(['customer_id' => $customer->id]);
            $order = Order::factory()->create([
                'number' => $orderNumber,
                'customer_id' => $customer->id,
                'order_status_id' => $orderStatus->id
            ]);
        }
        $this->shipmentAssigment = [$shipmentNumber => $order->id];
    }

    /**
     * @Then the client :customerName should have a balance of :amount
     */
    public function theClientShouldHaveABalanceOf(string $customerName, string $amount): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $balance= BillingBalance::where(['threepl_id' => $customer->parent_id, 'client_id' => $customer->id])->firstOrFail();

        $this->assertEquals($amount, $balance->amount);
    }

    /**
     * @Given the warehouse :warehouseName has a location called :locationName
     */
    public function theWarehouseFromCustomerHasALocationCalled($warehouseName, $locationName)
    {
        $warehouse = Warehouse::where('customer_id', $this->getCustomerInScope()->id)
            ->whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
                $query->where('name', $warehouseName);
            })
            ->firstOrFail();
        return Location::factory()->create([
            'location_type_id' => null,
            'warehouse_id' => $warehouse->id,
            'name' => $locationName
        ]);
    }

    /**
     * @Given the warehouse :warehouseName has a receiving location called :locationName
     */
    public function theWarehouseFromCustomerHasAReceivingLocationCalled($warehouseName, $locationName)
    {
        $this->theWarehouseFromCustomerHasALocationCalled($warehouseName, $locationName)
            ->updateOrFail([
                'is_receiving' => true
            ]
        );
    }

    /**
     * @Given the warehouse :warehouseName has a pickable location called :locationName
     */
    public function theWarehouseFromCustomerHasAPickableLocationCalled($warehouseName, $locationName)
    {
        $this->theWarehouseFromCustomerHasALocationCalled($warehouseName, $locationName)
            ->updateOrFail([
                    'pickable' => true,
                    'sellable' => true
                ]
            );
    }

    /**
     * @Given the warehouse :warehouseName has a non-sellable location called :locationName
     */
     public function theWarehouseFromCustomerHasANonSellableLocationCalled($warehouseName, $locationName)
    {
        $this->theWarehouseFromCustomerHasALocationCalled($warehouseName, $locationName)
            ->updateOrFail([
                    'pickable' => true,
                    'sellable' => false
                ]
            );
    }

    /**
     * @Given the shipping carrier :carrierName is deactivated
     */
    public function theShippingCarrierIsDeactivated(string $carrierName): void
    {
        $deletingCarrier = ShippingCarrier::query()
            ->with('shippingMethods')
            ->where('name', $carrierName)->firstOrFail(['id', 'name']);

        $deletingCarrier->delete();
    }

    /**
     * @param $shippingMethod
     * @param string|null $trackingNumber
     * @param string|null $cost
     * @return void
     */
    private function mockShippingProviderByShippingMethod($shippingMethod, ?string $trackingNumber = null, ?string $cost = null): void
    {
        $genericShippingProviderMock = new GenericShippingProviderMock($shippingMethod, $trackingNumber, $cost);
        $shippingComponentMock = $this->getMockBuilder(ShippingComponent::class)
            ->onlyMethods(['getShippingProvider'])
            ->getMock();

        $shippingComponentMock->expects($this->once())
            ->method('getShippingProvider')
            ->willReturn($genericShippingProviderMock);

        $this->app->bind(ShippingComponent::class, function ($app) use ($shippingComponentMock) {
            return $shippingComponentMock;
        });
    }
}
