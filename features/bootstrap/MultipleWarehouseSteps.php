<?php

use App\Components\Allocation\MultiWarehouseComponent;
use App\Components\InventoryLogComponent;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Order\StoreRequest as OrderStoreRequest;
use App\Http\Requests\Order\UpdateRequest as OrderUpdateRequest;
use Carbon\Carbon;
use App\Models\{ContactInformation,
    Customer,
    CustomerSetting,
    Location,
    Order,
    OrderItem,
    Product,
    ProductWarehouse,
    PurchaseOrder,
    Supplier,
    Warehouse};
use Illuminate\Database\Eloquent\Builder;

/**
 * Behat steps to test Multiwarehouse support
 */
trait MultipleWarehouseSteps
{
    protected FormRequest $formRequest;
    /**
     * @Given a transfer order with number :orderNumber assigned to the warehouse :warehouseName with :quantity SKU :sku
     */
    public function anOrderWithNumberThatBelongsToWarehouseWithSKU(string $orderNumber, string $warehouseName, int $quantity, string $sku)
    {
        $warehouse = Warehouse::whereHas('contactInformation', static function (Builder $query) use (&$warehouseName) {
            $query
                ->where('name', $warehouseName)
                ->where('object_type', Warehouse::class);
        })
            ->firstOrFail();

        $product = Product::whereSku($sku)->firstOrFail();

        $product->quantity_on_hand += $quantity;

        $product->save();

        $formRequest = OrderStoreRequest::make([
            'customer_id' => $warehouse->customer_id,
            'number' => $orderNumber,
            'order_type' => Order::ORDER_TYPE_TRANSFER,
            'shipping_warehouse_id' => $warehouse->id,
            'shipping_vendor_id' => $warehouse->customer->suppliers()->first()->id,
            'order_items' => [[
                'product_id' => $product->id,
                'sku' => $product->sku,
                'quantity' => $quantity
            ]]
        ]);

        $this->formRequest = $formRequest;

        app('order')->store($this->formRequest, false);
    }

    /**
     * @Given the order :order has :quantity products with SKU :sku
     */
    public function theOrderHasProductSWithSKU(string $order, int $quantity, string $sku)
    {
        $order = Order::where('number', $order)->firstOrFail();

        $product = Product::whereSku($sku)->first();

        if (!$product) {
            $product = Product::factory()->create([
                'sku' => $sku,
                'name' => $sku,
                'quantity_on_hand' => $quantity
            ]);
        } else {
            $product->quantity_on_hand += $quantity;
            $product->save();
        }

        OrderItem::factory()->create([
            'product_id' => $product->id,
            'order_id' => $order->id,
            'quantity' => $quantity
        ]);
    }

    /**
     * @Given the warehouse :warehouseName belongs to customer :customerName
     */
    public function theWarehouseBelongsToCustomer(string $warehouseName, string $customerName)
    {
        $customer = Customer::whereHas('contactInformation', static function (Builder $query) use ($customerName) {
            $query->where('name', $customerName);
        })
            ->firstOrFail();

        $warehouse = Warehouse::factory()->create([
            'customer_id' => $customer->id,
        ]);

        ContactInformation::factory()->create([
            'object_type' => Warehouse::class,
            'object_id' => $warehouse->id,
            'country_id' => $customer->contactInformation->country_id,
            'name' => $warehouseName
        ]);
    }

    /**
     * @Given the warehouse :warehouseName set as default to the customer :customerName
     */
    public function theWarehouseSetAsDefaultToTheCustomer(string $warehouseName, string $customerName)
    {
        $customer = Customer::whereHas('contactInformation', static function (Builder $query) use ($customerName) {
            $query->where('name', $customerName);
        })
            ->firstOrFail();

        $warehouse = Warehouse::where('customer_id', $customer->id)
            ->whereHas('contactInformation', static function (Builder $query) use ($warehouseName) {
                $query->where('name', $warehouseName);
            })
            ->firstOrFail();

        CustomerSetting::updateOrCreate(
            ['customer_id' => $customer->id, 'key' => CustomerSetting::CUSTOMER_SETTING_DEFAULT_WAREHOUSE],
            ['value' => $warehouse->id]
        );
    }

    /**
     * @Then the warehouse :warehouseName is assigned to the order :orderNumber
     */
    public function theWarehouseIsAssignedToTheOrder(string $warehouseName, string $orderNumber)
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();

        $warehouseId = customer_settings($order->customer->id, CustomerSetting::CUSTOMER_SETTING_DEFAULT_WAREHOUSE);

        if (!$warehouseId && $order->customer->is3plChild()) {
            $warehouseId = customer_settings($order->customer->parent->id, CustomerSetting::CUSTOMER_SETTING_DEFAULT_WAREHOUSE);
        }

        $warehouse = Warehouse::find($warehouseId);

        $this->assertEquals($warehouseId, $order->warehouse->id);
        $this->assertEquals($warehouse->contactInformation->name, $warehouseName);
    }

    /**
     * @Then order with number :orderNumber has a warehouse assigned to it
     */
    public function orderWithNumberHasAWarehouseAssignedToIt($orderNumber)
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();

        $this->assertNotNull($order->warehouse_id);
    }

    /**
     * @Given a product with SKU :sku that belongs to customer :customerName
     */
    public function aProductWithSKUThatBelongsToCustomer($sku, $customerName)
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })
            ->firstOrFail();

        Product::factory()->create([
            'customer_id' => $customer->id,
            'sku' => $sku,
            'name' => $sku
        ]);
    }

    /**
     * @Given the quantity on hand for product :sku in location :locationName and in warehouse :warehouseName is :quantityOnHand
     */
    public function theQuantityOnHandInLocationForProductInWarehouseIs($sku, $locationName, $warehouseName, $quantityOnHand)
    {
        $product = Product::whereSku($sku)->firstOrFail();

        $warehouse = Warehouse::whereHas('contactInformation', static function (Builder $query) use (&$warehouseName) {
            $query
                ->where('name', $warehouseName)
                ->where('object_type', Warehouse::class);
        })
            ->firstOrFail();

        $location = Location::whereName($locationName)
            ->where('warehouse_id', $warehouse->id)
            ->firstOrFail();

        app('inventoryLog')->adjustInventory(
            $location,
            $product,
            $quantityOnHand,
            InventoryLogComponent::OPERATION_TYPE_MANUAL
        );

        app('allocation')->allocateInventory($product);

        $multiwarehouse = new MultiWarehouseComponent();

        $multiwarehouse->allocateInventory($product, $warehouse);
    }

    /**
     * @When I allocate the inventory for :sku in all warehouses for customer :customerName
     */
    public function iAllocateTheInventoryForForCustomer($sku, $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', static function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })
            ->firstOrFail();

        $product = Product::whereSku($sku)->firstOrFail();

        foreach ($customer->warehouses as $warehouse) {
            app('allocation')->allocateInventory($product, $warehouse);
        }
    }

    /**
     * @Then the quantity on hand for :sku is :quantityOnHand
     */
    public function theQuantityOnHandForIs($sku, $quantityOnHand)
    {
        $product = Product::whereSku($sku)->firstOrFail();

        $this->assertEquals($product->quantity_on_hand, $quantityOnHand);
    }

    /**
     * @Given location :locationName belongs to warehouse :warehouseName
     */
    public function locationBelongsToWarehouse($locationName, $warehouseName)
    {
        $warehouse = Warehouse::whereHas('contactInformation', static function (Builder $query) use ($warehouseName) {
            $query
                ->where('name', $warehouseName)
                ->where('object_type', Warehouse::class);
        })
            ->firstOrFail();

        Location::create([
            'name' => $locationName,
            'warehouse_id' => $warehouse->id,
            'pickable' => 1,
            'sellable' => 1
        ]);
    }

    /**
     * @Given a supplier that belong to customer :customerName
     */
    public function aSupplierThatBelongToCustomer($customerName)
    {
        $customer = Customer::whereHas('contactInformation', static function (Builder $query) use ($customerName) {
            $query->where('name', $customerName);
        })
            ->firstOrFail();

        $supplier = Supplier::factory()->create([
            'customer_id' => $customer->id,
        ]);

        ContactInformation::factory()->create([
            'object_type' => Warehouse::class,
            'object_id' => $supplier->id,
            'country_id' => $customer->contactInformation->country_id
        ]);
    }

    /**
     * @Then I will have a purchase order with number :poNumber that belongs to warehouse :warehouseName
     */
    public function iWillHaveAPurchaseOrderWithNumberThatBelongsToWarehouse($poNumber, $warehouseName)
    {
        $warehouse = Warehouse::whereHas('contactInformation', static function (Builder $query) use ($warehouseName) {
            $query->where('name', $warehouseName)
                ->where('object_type', Warehouse::class);
        })
            ->firstOrFail();

        $purchaseOrder = PurchaseOrder::whereNumber($poNumber)->firstOrFail();

        $this->assertEquals($purchaseOrder->warehouse_id, $warehouse->id);
    }

    /**
     * @Given the quantity on hand for product :sku in warehouse :warehouseName is :quantity
     */
    public function theQuantityOnHandForProductInWarehouseIs($sku, $warehouseName, $quantity)
    {
        $warehouse = Warehouse::whereHas('contactInformation', static function (Builder $query) use ($warehouseName) {
            $query->where('name', $warehouseName)
                ->where('object_type', Warehouse::class);
        })
            ->firstOrFail();

        $product = Product::whereSku($sku)->firstOrFail();

        $warehouseProduct = ProductWarehouse::whereWarehouseId($warehouse->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertEquals($warehouseProduct->quantity_on_hand, (int) $quantity);
    }

    /**
     * @Then the shipping address for order :orderNumber is the same as the address from warehouse :warehouseName
     */
    public function theShippingAddressForOrderIsTheSameAsTheAddressFromWarehouse($orderNumber, $warehouseName)
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();

        $warehouse = Warehouse::whereHas('contactInformation', static function (Builder $query) use ($warehouseName) {
            $query
                ->where('name', $warehouseName)
                ->where('object_type', Warehouse::class);
        })
            ->firstOrFail();

        $this->assertEquals($warehouse->contactInformation->id, $order->shippingContactInformation->id);
    }

    /**
     * @Given an order with number :orderNumber that belongs to customer :customerName
     */
    public function anOrderWithNumberThatBelongsToCustomer($orderNumber, $customerName)
    {
        $customer = Customer::whereHas('contactInformation', static function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })
            ->firstOrFail();

        $order = Order::factory()->make([
            'customer_id' => $customer->id,
            'number' => $orderNumber
        ]);

        $order->save();
    }

    /**
     * @Given I assign the order :orderNumber to the warehouse :warehouseName
     */
    public function iAssignTheOrderToTheWarehouse($orderNumber, $warehouseName)
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();

        $warehouse = Warehouse::whereHas('contactInformation', static function (Builder $query) use ($warehouseName) {
            $query
                ->where('name', $warehouseName)
                ->where('object_type', Warehouse::class);
        })
            ->firstOrFail();

        $order->warehouse_id = $warehouse->id;
        $order->save();


    }

    /**
     * @Then a transfer order with number :orderNumber assigned to the same warehouse :warehouseName with :quantity SKU :sku will fail
     */
    public function aTransferOrderWithNumberAssignedToTheSameWarehouseWithSKU($orderNumber, $warehouseName, $quantity, $sku)
    {
        $warehouse = Warehouse::whereHas('contactInformation', static function (Builder $query) use (&$warehouseName) {
            $query
                ->where('name', $warehouseName)
                ->where('object_type', Warehouse::class);
        })
            ->firstOrFail();

        $product = Product::whereSku($sku)->firstOrFail();

        $product->quantity_on_hand += $quantity;

        $product->save();

        $validationErrors = OrderStoreRequest::getValidationErrors([
            'customer_id' => $warehouse->customer_id,
            'number' => $orderNumber,
            'order_type' => Order::ORDER_TYPE_TRANSFER,
            'shipping_warehouse_id' => $warehouse->id,
            'shipping_vendor_id' => $warehouse->customer->suppliers()->first()->id,
            'warehouse_id' => $warehouse->id,
            'order_items' => [[
                'product_id' => $product->id,
                'sku' => $product->sku,
                'quantity' => $quantity
            ]]
        ]);

        foreach ($validationErrors->getMessages() as $validationError) {
            $this->assertContains('Receiving and sending warehouses cannot be the same', $validationError);
        }
    }
}
