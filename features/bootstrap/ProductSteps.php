<?php

use App\Http\Requests\Product\AddToLocationRequest;
use App\Models\{Customer, Order, OrderItem, Product, Tag, Location, LocationType,Warehouse, Lot, Supplier};
use App\Http\Requests\Product\UpdateRequest as ProductUpdateRequest;
use App\Http\Requests\Product\StoreRequest as ProductStoreRequest;
use App\Http\Requests\Product\TransferRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Validation\ValidationException;
use Behat\Gherkin\Node\TableNode;

/**
 * Behat steps to test products.
 */
trait ProductSteps
{
    protected Product|null $productInScope = null;
    protected OrderItem|null $orderLineInScope = null;
    protected array $requestData = [];

    /**
     * @Given the customer :customerName has an SKU :sku named :productName priced at :price
     */
    public function theCustomerHasAnSkuNamedPricedAt(
        string $customerName, string $sku, string $productName, string $price
    ): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $this->productInScope = Product::factory()->create([
            'customer_id' => $customer->id,
            'sku' => $sku,
            'name' => $productName,
            'price' => $price,
            'type' => Product::PRODUCT_TYPE_REGULAR,
            'hs_code' => 'string'
        ]);
    }

    /**
     * @Given the customer :customerName has an SKU :sku named :productName priced at :price with weighing :weight
     */
    public function theCustomerHasAnSkuNamedPricedAtWithWeighing(
        string $customerName, string $sku, string $productName, string $price, string $weight
    ): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $this->productInScope = Product::factory()->create([
            'customer_id' => $customer->id,
            'sku' => $sku,
            'name' => $productName,
            'price' => $price,
            'type' => Product::PRODUCT_TYPE_REGULAR,
            'hs_code' => 'string',
            'weight' => $weight
        ]);
    }

    /**
     * @Given the customer :customerName start to create an SKU :sku named :productName priced at :price and set product type as :typeValue
     */
    public function theCustomerHasAnSkuNamedPricedAtAndSetProductTypeAs(
        string $customerName, string $sku, string $productName, string $price, string $typeValue = 'regular'
    ): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $this->requestData = [
            'customer_id' => $customer->id,
            'sku' => $sku,
            'name' => $productName,
            'price' => $price,
            'type' => $typeValue,
            'hs_code' => 'string'
        ];
    }

    /**
     * @Given the customer :customerName has an SKU :sku named :productName priced at :price and based in :countryName
     */
    public function theCustomerHasAnSkuNamedPricedAtAndBasedIn(
        string $customerName, string $sku, string $productName, string $price, string $countryName
    ): void
    {
        $country = \Countries::where('name', $countryName)->firstOrFail();
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $this->productInScope = Product::factory()->create([
            'customer_id' => $customer->id,
            'sku' => $sku,
            'name' => $productName,
            'price' => $price,
            'hs_code' => 'string',
            'country_of_origin' => $country->id
        ]);
    }

    /**
     * @Given the customer :customerName has an SKU :sku named :productName weighing :weight
     */
    public function theCustomerHasAnSkuNamedWeighing(
        string $customerName, string $sku, string $productName, string $weight
    ): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $this->productInScope = Product::factory()->create([
            'customer_id' => $customer->id,
            'sku' => $sku,
            'name' => $productName,
            'weight' => $weight
        ]);
    }

    /**
     * @Given the product's type is set to virtual
     */
    public function theProductsTypeIsSetToVirtual(): void
    {
        $this->productInScope->type = Product::PRODUCT_TYPE_VIRTUAL;
        $this->productInScope->save();
    }

    /**
     * @Given the product has a weight of :weight
     */
    public function theProductHasAWeightOf(string $weight): void
    {
        $this->productInScope->weight = $weight;
        $this->productInScope->save();
    }

    /**
     * @Given the customer :customerName has an SKU :sku named :productName and barcoded :barcode
     */
    public function theCustomerHasAnSkuNamedAndBarcoded(
        string $customerName, string $sku, string $productName, string $barcode
    ): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $this->productInScope = Product::factory()->create([
            'customer_id' => $customer->id,
            'sku' => $sku,
            'name' => $productName,
            'barcode' => $barcode
        ]);
    }

    /**
     * @Given the customer :customerName has a product named :productName with SKU :sku weighing :weight and sized :length x :width x :height
     */
    public function theCustomerHasAnProductNamedWithSkuIsWeighingAndSizedXX(
        string $customerName, string $sku, string $productName, string $weight, string $length, string $width, string $height
    ): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $this->productInScope = Product::factory()->create([
            'customer_id' => $customer->id,
            'sku' => $sku,
            'name' => $productName,
            'weight' => $weight,
            'length' => $length,
            'width' => $width,
            'height' => $height
        ]);
    }

    /**
     * @Given the customer :customerName has an SKU :sku named :productName sized :length x :width x :height
     */
    public function theCustomerHasAnSkuNamedSizedXX(
        string $customerName, string $sku, string $productName, string $length, string $width, string $height
    ): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $this->productInScope = Product::factory()->create([
            'customer_id' => $customer->id,
            'sku' => $sku,
            'name' => $productName,
            'length' => $length,
            'width' => $width,
            'height' => $height
        ]);
    }

    /**
     * @Given the warehouse :warehouseName had quantity :quantity that attached in product with location :locationTypeName
     */
    public function theWarehouseHadQuantityThantAttachedInProductWithLocation(
        string $warehouseName,
        string $quantity,
        string $locationTypeName
    ): void
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

        $locationAttributes = [
            'warehouse_id' => $warehouse->id,
            'location_type_id' => $locationType->id,
            'pickable' => $locationType->pickable,
            'sellable' => $locationType->sellable
        ];

        $location = Location::where($locationAttributes)->first();

        if (is_null($location)) {
            $location = Location::factory()->create($locationAttributes);
        }

        $product = $this->productInScope;
        $product->locations()->attach($location->id, ['quantity_on_hand' => $quantity]);
        $product->save();
    }

    /**
     * @Given the SKU :sku of client :customerName is tagged as :tagName
     */
    public function theSkuOfClientIsTaggedAs(string $sku, string $customerName, string $tagName)
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $product = Product::where(['customer_id' => $customer->id, 'sku' => $sku])->firstOrFail();
        $tag = Tag::create(['customer_id' => $customer->id, 'name' => $tagName]);
        $product->tags()->attach($tag);
        $product->save();
    }

    /**
     * @Given the SKU :sku is added as a component to the kit product with quantity of :quantity
     */
    public function theSkuIsAddedAsAComponentToTheKitProductWithQuantityOf(string $sku, string $quantity): void
    {
        $this->theProductTypeIsChangedToStaticKit();

        $component = Product::where(['customer_id' => $this->productInScope->customer_id, 'sku' => $sku])->firstOrFail();
        $this->productInScope->kitItems()->attach($component->id, ['quantity' => $quantity]);
        $this->productInScope->save();
    }


    /**
     * @Given the customer :customerName deletes product with SKU :sku
     */
    public function theCustomerDeletesProductWithSku(string $customerName, string $sku): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $product = Product::where(['customer_id' => $customer->id, 'sku' => $sku])->firstOrFail();

        $product->delete();
    }

    /**
     * @Given the component SKU :sku is removed from the kit product
     */
    public function theComponentSKUIsRemovedFromTheKitProduct(string $sku)
    {
        $component = Product::where(['customer_id' => $this->productInScope->customer_id, 'sku' => $sku])->firstOrFail();
        $this->productInScope->kitItems()->detach($component->id);
    }

    /**
     * @Given the product has a supplier called :supplierName
     */
    public function theProductHasASupplierCalled($supplierName): void
    {
        $supplier = Supplier::where('customer_id', $this->productInScope->customer_id)
            ->whereHas('contactInformation', fn(Builder $query) => $query->where('name', $supplierName))
            ->first();

        if (!$supplier) {
            $supplier = Supplier::factory()->create([
                'customer_id' => $this->productInScope->customer_id
            ]);

            $supplier->contactInformation()->create([
                'name' => $supplierName
            ]);
        }

        $this->productInScope->suppliers()->syncWithoutDetaching($supplier);
    }

    /**
     * @Given the product's customs price is :customsPrice
     */
    public function theProductsCustomsPriceIs(float|string $customsPrice): void
    {
        if ($customsPrice === "null") {
            $customsPrice = null;
        }
        $this->productInScope->update([
            'customs_price' => $customsPrice
        ]);
    }

    /**
     * @Given the product has lot tracking set to :value
     */
    public function theProductHasLotTrackingSetTo($value): void
    {
        $this->productInScope->update([
            'lot_tracking' => $value
        ]);
    }

    /**
     * @Given the product has lot priority set to :value
     */
    public function theProductHasLotPrioritySetTo($value): void
    {
        $this->productInScope->update([
            'lot_priority' => $value
        ]);
    }

    /**
     * @Given the product has lot called :lotName expiring on :expirationDate from supplier :supplierName
     */
    public function theProductHasLotCalledExpiringOnFromSupplier($lotName, $expirationDate, $supplierName): void
    {
        $expirationDate = Carbon::parse($expirationDate);
        $supplier = Supplier::where('customer_id', $this->productInScope->customer_id)
            ->whereHas('contactInformation', fn(Builder $query) => $query->where('name', $supplierName))
            ->firstOrFail();

        Lot::firstOrCreate([
            'customer_id' => $this->productInScope->customer_id,
            'product_id' => $this->productInScope->id,
            'supplier_id' => $supplier->id,
            'name' => $lotName,
            'expiration_date' => $expirationDate
        ]);
    }

    /**
     * @Then the product should have inventory :quantity on location :locationName
     */
    public function theProductShouldHaveInventoryOnLocation($quantity, $locationName): void
    {
        $product = $this->getProductInScope();

        $this->assertEquals(
            $quantity,
            $product->locations()->where('name', $locationName)->first()->pivot->quantity_on_hand ?? 0
        );
    }

    /**
     * @Then the product lot report should show added :quantityAdded, removed :quantityRemoved and remaining :quantityRemaining for lot :lotName on location :locationName
     */
    public function theProductLotReportShouldShowAddedRemovedAndRemainingForLotOnLocation($quantityAdded,
                                                                                          $quantityRemoved,
                                                                                          $quantityRemaining,
                                                                                          $lotName,
                                                                                          $locationName): void
    {
        $lotItem = $this->getProductInScopeLotItem($lotName, $locationName);

        $this->assertEquals(
            $quantityAdded,
            $lotItem->quantity_added
        );

        $this->assertEquals(
            $quantityRemoved,
            $lotItem->quantity_removed
        );

        $this->assertEquals(
            $quantityRemaining,
            $lotItem->quantity_remaining
        );
    }

    /**
     * @Then the product lot report should show nothing for lot :lotName on location :locationName
     */
    public function theProductLotReportShouldShowNothingForLotOnLocation($lotName, $locationName): void
    {
        $thrownException = null;

        try {
            $this->getProductInScopeLotItem($lotName, $locationName);
        } catch (Exception $exception) {
            $thrownException = $exception;
        }

        $this->assertInstanceOf(ItemNotFoundException::class, $thrownException);
    }

    private function getProductInScopeLotItem($lotName, $locationName)
    {
        $product = $this->getProductInScope();
        $warehouse = $this->getWarehouseInScope();
        $location = $warehouse->locations->where('name', $locationName)->firstOrFail();
        $lot = $product->lots->where('name', $lotName)->firstOrFail();

        return $lot->lotItems->where('location_id', $location->id)->firstOrFail();
    }

    /**
     * @When I manually set :quantity of :sku into :locationName location
     */
    public function iManuallyAddOfIntoLocation($quantity, $sku, $locationName): void
    {
        $this->iManuallyAddOfWithLotIntoLocation($quantity, $sku, null, $locationName);
    }

    /**
     * @When I manually set :quantity of :sku with lot :lotName into :locationName location
     */
    public function iManuallyAddOfWithLotIntoLocation($quantity, $sku, $lotName, $locationName): void
    {
        $customer = $this->getCustomerInScope();
        $warehouse = $this->getWarehouseInScope();
        $product = $customer->products()->where('sku', $sku)->firstOrFail();
        $location = $warehouse->locations->where('name', $locationName)->firstOrFail();
        $lot = null;

        if ($lotName) {
            $lot = $product->lots->where('name', $lotName)->firstOrFail();
        }

        if (method_exists($this, 'setProductInScope')) {
            $this->setProductInScope($product);
        }

        $addToLocationRequest = AddToLocationRequest::make([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'lot_id' => $lot->id ?? null,
            'quantity' => $quantity
        ]);

        app('product')->addToLocation($addToLocationRequest, $product);
    }

    /**
     * @Then I shouldn't be able to manually set :quantity of :sku into :locationName location
     */
    public function iShouldnTBeAbleToManuallySetOfIntoLocation($quantity, $sku, $locationName): void
    {
        $this->iShouldnTBeAbleToManuallySetOfWithLotIntoLocation($quantity, $sku, null, $locationName);
    }

    /**
     * @Then I shouldn't be able to manually set :quantity of :sku with lot :lotName into :locationName location
     */
    public function iShouldnTBeAbleToManuallySetOfWithLotIntoLocation($quantity, $sku, $lotName, $locationName): void
    {
        $thrownException = null;

        try {
            $this->iManuallyAddOfWithLotIntoLocation($quantity, $sku, $lotName, $locationName);
        } catch (Exception $exception) {
            $thrownException = $exception;
        }

        $this->assertInstanceOf(ValidationException::class, $thrownException);
    }

    /**
     * @When I transfer :quantity of :sku from :fromLocationName location into :toLocationName location
     */
    public function iTransferOfFromLocationIntoLocation($quantity, $sku, $fromLocationName, $toLocationName)
    {
        $customer = $this->getCustomerInScope();
        $warehouse = $this->getWarehouseInScope();
        $product = $customer->products->where('sku', $sku)->firstOrFail();
        $fromLocation = $warehouse->locations->where('name', $fromLocationName)->firstOrFail();
        $toLocation = $warehouse->locations->where('name', $toLocationName)->firstOrFail();

        if (method_exists($this, 'setProductInScope')) {
            $this->setProductInScope($product);
        }

        $transferRequest = TransferRequest::make([
            'product_id' => $product->id,
            'from_location_id' => $fromLocation->id,
            'to_location_id' => $toLocation->id,
            'quantity' => $quantity
        ]);

        app('product')->transferInventory($transferRequest, $product);
    }

    /**
     * @When I transfer :quantity of :sku with lot :lotName from :fromLocationName location into :toLocationName location
     */
    public function iTransferOfWithLotFromLocationIntoLocation($quantity, $sku, $lotName, $fromLocationName, $toLocationName)
    {
        $customer = $this->getCustomerInScope();
        $warehouse = $this->getWarehouseInScope();
        $product = $customer->products->where('sku', $sku)->firstOrFail();
        $fromLocation = $warehouse->locations->where('name', $fromLocationName)->firstOrFail();
        $toLocation = $warehouse->locations->where('name', $toLocationName)->firstOrFail();

        if (method_exists($this, 'setProductInScope')) {
            $this->setProductInScope($product);
        }

        $lot = null;

        if ($lotName) {
            $lot = $product->lots->where('name', $lotName)->firstOrFail();
        }

        $transferRequest = TransferRequest::make([
            'product_id' => $product->id,
            'lot_id' => $lot->id ?? null,
            'from_location_id' => $fromLocation->id,
            'to_location_id' => $toLocation->id,
            'quantity' => $quantity
        ]);

        app('product')->transferInventory($transferRequest, $product);
    }

    /**
     * @Then I shouldn't be able to transfer :quantity of :sku from :fromLocationName location into :toLocationName location
     */
    public function iShouldnTBeAbleToTransferOfFromLocationIntoLocation($quantity,
                                                                        $sku,
                                                                        $fromLocationName,
                                                                        $toLocationName)
    {
        $thrownException = null;

        try {
            $this->iTransferOfFromLocationIntoLocation($quantity, $sku, $fromLocationName, $toLocationName);
        } catch (Exception $exception) {
            $thrownException = $exception;
        }

        $this->assertInstanceOf(ValidationException::class, $thrownException);
    }

    /**
     * @Then the kit product has :quantity components
     */
    public function theKitProductHasComponents(int $quantity)
    {
        $this->assertEquals($quantity, $this->productInScope->kitItems()->count());
    }

    /**
     * @Given the customer :customerName with product named :productName has changed SKU to value :value
     */
    public function theCustomerWithProductNamedHasChangedSkuToValue(string $customerName, string $productName, string $value): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $product = Product::where(['customer_id' => $customer->id, 'name' => $productName])->firstOrFail();
        $product->sku = $value;
        $product->save();
    }

    /**
     * @Then the product type is kit
     */
    public function theProductTypeIsKit(): void
    {
        $product = $this->productInScope;

        $this->assertTrue($product->isKit());
    }

    /**
     * @Then the product type is regular
     */
    public function theProductTypeIsRegular(): void
    {
        $product = $this->productInScope;

        $this->assertTrue($product->type == Product::PRODUCT_TYPE_REGULAR);
    }

    /**
     * @Then the product type is virtual
     */
    public function theProductTypeIsVirtual(): void
    {
        $product = $this->productInScope;

        $this->assertTrue($product->type == Product::PRODUCT_TYPE_VIRTUAL);
    }

    /**
     * @When I allocate the SKU :sku
     */
    public function iAllocateTheSku(string $sku): void
    {
        $product = Product::where(['sku' => $sku])->firstOrFail();

        app('allocation')->allocateInventory($product);
    }

    /**
     * @Given the product type is changed to static kit
     */
    public function theProductTypeIsChangedToStaticKit(): void
    {
        $this->productInScope->type = Product::PRODUCT_TYPE_STATIC_KIT;
        $this->productInScope->save();
    }

    /**
     * @Given the user opens product edit form for SKU :sku
     */
    public function theUserOpensProductEditFormForSku(string $sku): void
    {
        $product = $this->getCustomerInScope()->products()
            ->where('sku', $sku)
            ->firstOrFail();

        $this->productInScope = $product;

        $this->requestData = [
            'id' => $product->id
        ];

        $this->mockRequest('PUT', 'product.update', ['product' => $product]);
    }

    /**
     * @Given the user sets the product type to static kit
     */
    public function theUserSetsTheProductTypeToStaticKit(): void
    {
        $this->requestData['type'] = Product::PRODUCT_TYPE_STATIC_KIT;
    }

    /**
     * @Given the user adds component SKU :sku with quantity :quantity to the form data
     */
    public function theUserAddsComponentSkuWithQuantityToTheFormData(string $sku, $quantity): void
    {
        $customer = $this->getCustomerInScope();
        $componentProduct = Product::where(['sku' => $sku, 'customer_id' => $customer->id])->firstOrFail();

        if (!isset($this->requestData['kit_items'])) {
            $this->requestData['kit_items'] = [];
        }

        $this->requestData['kit_items'][] = [
            'quantity' => $quantity,
            'id' => $componentProduct->id
        ];
    }

    /**
     * @Given the user from warehouse :warehouseName adds inventory in location :locationName with quantity of :quantity to the form data
     */
    public function theUserFromWarehouseAddsInventoryInLocationWithQuantityOfToTheFormData(string $warehouseName, string $locationName, $quantity): void
    {
        $warehouse = Warehouse::whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
            $query->where('name', $warehouseName);
        })->firstOrFail();

        $location = Location::whereWarehouseId($warehouse->id)
                ->where('name', $locationName)
                ->firstOrFail();

        if (!isset($this->requestData['product_locations'])) {
            $this->requestData['product_locations'] = [];
        }

        $this->requestData['product_locations'][] = [
            'quantity' => $quantity,
            'id' => $location->id
        ];
    }

    /**
     * @Given the user change a product value of :fieldName to :value in the form data
     */
    public function theUserChangeAProductValueOfFieldToValueInTheFormData(string $fieldName, $value)
    {
        $this->requestData[$fieldName] = $value;
    }

    /**
     * @Then the user validates the product update form
     */
    public function theUserValidatesTheProductUpdateForm(): void
    {
        $validator = ProductUpdateRequest::getValidationErrors($this->requestData);

        $this->checkMessageBag($validator);
    }

    /**
     * @Then the user submits the product update form
     */
    public function theUserSubmitsTheProductUpdateForm(): void
    {
        $formRequest = ProductUpdateRequest::make($this->requestData);

        $this->requestData['customer_id'] = $this->productInScope->customer_id;

        app('product')->update($formRequest, $this->productInScope);

        $this->requestData = [];
    }

    /**
     * @Then the user validates the product store form
     */
    public function theUserValidatesTheProductStoreForm(): void
    {
        $validator = ProductStoreRequest::getValidationErrors($this->requestData);

        $this->checkMessageBag($validator);
    }

    /**
     * @Then the user submits the product store form
     */
    public function theUserSubmitsTheProductStoreForm(): void
    {
        $formRequest = ProductStoreRequest::make($this->requestData);

        $product = app('product')->store($formRequest);

        $this->productInScope = $product;

        $this->requestData = [];
    }

    /**
     * @When the kit product is synced with pending order items
     */
    public function theKitProductIsSyncedWithPendingOrderItems(): void
    {
        $product = $this->productInScope;

        app('product')->syncKitProductWithOrderItems($product);
    }

    /**
     * @Given the product with SKU :sku is a parent kit
     */
    public function theProductIsAParentKit(string $sku): void
    {
        $this->productInScope = Product::where(['sku' => $sku])->firstOrFail();

        $this->productInScope->type = Product::PRODUCT_TYPE_STATIC_KIT;
        $this->productInScope->save();
    }

    /**
     * @Then the product :sku should have these tags
     */
    public function theProductShouldHaveTheseTags(string $sku, TableNode $tagsTable): void
    {
        $product = Product::where('sku', $sku)->firstOrFail();
        $expectedTags = $tagsTable->getRow(0);
        sort($expectedTags);
        $actualTags = $product->tags->pluck('name')->toArray();
        sort($actualTags);

        $this->assertEquals($expectedTags, $actualTags);
    }

    /**
     * @Then the product SKU :sku is allocated with quantity of :quantity
     */
    public function theProductSkuIsAllocatedWithQuantityOf(string $sku, $quantity): void
    {
        $product = Product::where('sku', $sku)->firstOrFail();

        $this->assertEquals($product->quantity_allocated, $quantity);
    }

    /**
     * @Then the product SKU :sku is on hand with quantity of :quantity
     */
    public function theProductSkuIsOnHandWithQuantityOf(string $sku, $quantity): void
    {
        $product = Product::where('sku', $sku)->firstOrFail();

        $this->assertEquals($product->quantity_on_hand, $quantity);
    }

    /**
     * @Then the customer :customerName has :quantity products with the same barcode :barcode
     */
    public function theCustomerHasProductsWithTheSameBarcode($customerName, $quantity, $barcode): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })
            ->firstOrFail();

        $productsNumber = Product::whereBarcode($barcode)->where('customer_id', $customer->id)->count();

        $this->assertEquals($quantity, $productsNumber);
    }

    /**
     * @Given the customer :customerName creates an SKU :sku named :name and barcoded :barcode using the store form
     */
    public function theCustomerCreatesAnSKUNamedAndBarcodedUsingTheCreateForm($customerName, $sku, $name, $barcode): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })
            ->firstOrFail();

        $this->requestData = [
            'sku' => $sku,
            'name' => $name,
            'barcode' => $barcode,
            'customer_id' => $customer->id
        ];
    }

    /**
     * @Given the user changes the SKU :sku barcode to :barcode
     */
    public function theUserChangesTheSKUBarcodeTo($sku, $barcode)
    {
        $customer = $this->getCustomerInScope();
        $product = Product::where(['sku' => $sku, 'customer_id' => $customer->id])->firstOrFail();

        $this->requestData = [
            'barcode' => $barcode,
            'id' => $product->id,
            'customer_id' => $customer->id
        ];
    }

    /**
     * @Given the user sets the product type to virtual
     */
    public function theUserSetsTheProductTypeToVirtual(): void
    {
        $this->requestData['type'] = Product::PRODUCT_TYPE_VIRTUAL;
    }

    /**
     * @Given the user sets the product type to regular
     */
    public function theUserSetsTheProductTypeToRegular(): void
    {
        $this->requestData['type'] = Product::PRODUCT_TYPE_REGULAR;
    }

    /**
     * @Given the product with SKU :sku is selected
     */
    public function theProductWithSkuIsSelected(string $sku): void
    {
        $product = Product::whereSku($sku)->firstOrFail();

        $this->setProductInScope($product);
        $this->productInScope = $product;
    }

    /**
     * @Given the product has a non-sellable quantity of :quantity
     */
    public function theProductHasANonSellableQuantityOf($quantity): void
    {
        $product = $this->productInScope;

        $product->quantity_non_sellable = (int) $quantity;

        $product->save();

        $this->assertEquals($product->quantity_non_sellable, $quantity);
    }

    /**
     * @Then the order :orderNumber should have the order line with SKU :sku
     */
    public function theOrderShouldHaveTheOrderLineWithSKU($orderNumber, $sku): void
    {
        $order = Order::whereNumber($orderNumber)->firstOrFail();
        $product = Product::whereSku($sku)->firstOrFail();

        $orderLine = null;

        foreach ($order->orderItems as $orderItem) {
            if ($orderItem->product_id === $product->id) {
                $orderLine = $orderItem;
            }
        }

        $this->assertNotNull($orderLine);

        $this->orderLineInScope = $orderLine;
    }

    /**
     * @Given the kit product has the following components attached
     */
    public function theKitProductHasTheFollowingComponentsAttached(TableNode $table): void
    {
        $product = $this->productInScope;

        $data = self::prepareKitItemsData($table->getRow(0), $table->getRow(1));

        $data['id'] = $product->id;
        $data['customer_id'] = $product->customer_id;

        $updateRequest = ProductUpdateRequest::make($data);

        app('product')->update($updateRequest, $product);
    }

    /**
     * @Then the product SKU :sku in location :locationName is on hand with quantity of :quantity
     */
    public function theProductSkuInLocationIsOnHandWithQuantityOf(string $sku, string $locationName, $quantity): void
    {
        $product = Product::whereSku($sku)->firstOrFail();

        $this->assertEquals(
            $quantity,
            $product->locations()->where('name', $locationName)->first()->pivot->quantity_on_hand ?? 0
        );
    }

    /**
     * @Given the order line should have :quantityPending items pending
     */
    public function theOrderLineShouldHaveItemsPending($quantityPending): void
    {
        $this->assertEquals($this->orderLineInScope->quantity_pending, $quantityPending);
    }

    /**
     * @Given the order line should have :quantityShipped items shipped
     */
    public function theOrderLineShouldHaveItemsShipped($quantityShipped): void
    {
        $this->assertEquals($this->orderLineInScope->quantity_shipped, $quantityShipped);
    }

    /**
     * @Then the product SKU :sku should have the following quantities
     */
    public function theProductSkuInLocationShouldHaveTheFollowingQuantities(string $sku, TableNode $quantitiesTable): void
    {
        $product = Product::whereSku($sku)->firstOrFail();
        self::checkProductQuantities($product, $quantitiesTable->getRow(0), $quantitiesTable->getRow(1));
    }

    /**
     * @param Product $product
     * @param array $fieldNames
     * @param array $fieldValues
     * @return void
     */
    public static function checkProductQuantities(Product $product, array $fieldNames, array $fieldValues): void
    {
        foreach ($fieldNames as $key => $fieldName) {
            self::assertEquals($fieldValues[$key], $product->{$fieldName}, "Failed asserting that field {$fieldName} is {$fieldValues[$key]} matches expected value {$product->{$fieldName}}");
        }

    }

    public static function prepareKitItemsData(array $componentSkus, array $componentQuantities): array
    {
        $data = [];
        foreach ($componentSkus as $key => $sku) {
            $product = Product::whereSku($sku)->firstOrFail();
            $data['kit_items'][] = [
                'id' => $product->id,
                'quantity' => $componentQuantities[$key],
            ];
        }

        return $data;
    }
}
