<?php

use App\Http\Requests\PurchaseOrder\ReceiveBatchRequest;
use App\Http\Requests\PurchaseOrder\ReceivePurchaseOrderRequest;
use App\Http\Requests\PurchaseOrder\StoreRequest as PurchaseOrderStoreRequest;
use App\Models\{Audit,
    CacheDocuments\PurchaseOrderCacheDocument,
    Customer,
    Location,
    Product,
    PurchaseOrder,
    PurchaseOrderItem,
    Supplier,
    Warehouse};
use App\Models\CacheDocuments\PurchaseOrderChargeCacheDocument;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Behat\Gherkin\Node\{PyStringNode, TableNode};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Behat steps to test customers.
 */
trait PurchaseOrderSteps
{
    protected PurchaseOrder|null $purchaseOrderInScope = null;
    protected Collection|null $purchaseOrders = null;
    protected ?array $purchaseOrderUpdateRequestData = null;

    /**
     * @Given the client :customerName has a pending purchase order :purchaseOrderNumber for :quantity of the SKU :sku
     */
    public function theClientHasAPendingPurchaseOrderForOfTheSku(
        string $customerName, string $purchaseOrderNumber, string $quantity, string $sku
    ): void
    {
        $this->theClientHasAPendingPurchaseOrderFromTheSupplierForOfTheSKU(
            $customerName,
            $purchaseOrderNumber,
            '',
            $quantity,
            $sku
        );
    }

    /**
     * @Given the SKU :sku on the purchase order :purchaseOrderNumber has quantity received of :quantity
     */
    public function theSkuOnThePurchaseOrderHasQuantityReceivedOf(string $sku, string $purchaseOrderNumber, string $quantity): void
    {
        $purchaseOrder = PurchaseOrder::where('number', $purchaseOrderNumber)->firstOrFail();
        $product = Product::where('sku', $sku)->firstOrFail();
        $purchaseOrderItem = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $purchaseOrderItem->quantity_received = $quantity;
        $purchaseOrderItem->save();
    }

    /**
     * @Given the client :customerName has a pending purchase order :purchaseOrderNumber from the supplier :supplierName for :quantity of the SKU :sku
     */
    public function theClientHasAPendingPurchaseOrderFromTheSupplierForOfTheSKU(
        string $customerName,
        string $purchaseOrderNumber,
        string $supplierName,
        string $quantity,
        string $sku
    )
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        if ($supplierName) {
            $supplier = Supplier::whereHas('contactInformation', function (Builder $query) use (&$supplierName) {
                $query->where('name', $supplierName);
            })->firstOrFail();
        } else {
            $supplier = Supplier::factory()->create(['customer_id' => $customer->id]);
        }
        $product = Product::where(['customer_id' => $customer->id, 'sku' => $sku])->firstOrFail();
        $formRequest = PurchaseOrderStoreRequest::make([
            'customer_id' => $customer->id,
            'warehouse_id' => $customer->parent ? $customer->parent->warehouses[0]->id : $customer->warehouses[0]->id,
            'supplier_id' => $supplier->id,
            'number' => $purchaseOrderNumber,
            'purchase_order_items' => [[
                'product_id' => $product->id,
                'quantity' => $quantity
            ]]
        ]);

        $this->purchaseOrderInScope = App::make('purchaseOrder')->store($formRequest, false);
    }

    /**
     * @Given the purchase order requires :quantity of the SKU :sku
     */
    public function thePurchaseOrderRequiresOfTheSku(string $quantity, string $sku): void
    {
        $product = Product::where([
            'customer_id' => $this->purchaseOrderInScope->customer_id,
            'sku' => $sku
        ])->firstOrFail();

        App::make('purchaseOrder')->updatePurchaseOrderItems($this->purchaseOrderInScope, [[
            'product_id' => $product->id,
            'quantity' => $quantity
        ]]);
    }

    /**
     * @When the purchase order :purchaseOrderNumber is received by the warehouse :warehouseName into the location :locationName
     */
    public function thePurchaseOrderIsReceivedByTheWarehouseIntoTheLocation(
        string $purchaseOrderNumber, string $warehouseName, string $locationName
    ): void
    {
        $warehouse = Warehouse::whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
            $query->where('name', $warehouseName);
        })->firstOrFail();
        $location = Location::where(['warehouse_id' => $warehouse->id, 'name' => $locationName])->firstOrFail();
        $this->purchaseOrderInScope = PurchaseOrder::where('number', $purchaseOrderNumber)->firstOrFail();

        foreach ($this->purchaseOrderInScope->purchaseOrderItems as $purchaseOrderItem) {
            $record[] = [
                'purchase_order_item_id' => $purchaseOrderItem->id,
                'quantity_received' => $purchaseOrderItem->quantity_pending,
                'location_id' => $location->id,
                'customer_id' => $warehouse->customer_id
            ];
        }

        app('purchaseOrder')->receiveBatch(ReceiveBatchRequest::make($record), $this->purchaseOrderInScope);
    }

    /**
     * @When it took :time :timeUnit to receive the purchase order
     */
    public function itTookToReceiveThePurchaseOrder(string $time, string $timeUnit): void
    {
        $audit = $this->purchaseOrderInScope->audits()->where('event', 'po-item-received')->orderBy('created_at')->first();
        $method = 'sub' . ucfirst(strtolower($timeUnit));
        $audit->created_at = $audit->created_at->$method($time);
        $audit->updated_at = $audit->updated_at->$method($time);
        $audit->save();
    }

    /**
     * @When the purchase order :purchaseOrderNumber is closed
     */
    public function thePurchaseOrderIsClosed(string $purchaseOrderNumber): void
    {
        $purchaseOrder = PurchaseOrder::where('number', $purchaseOrderNumber)->firstOrFail();

        app('purchaseOrder')->closePurchaseOrder($purchaseOrder);
    }

    /**
     * @When the purchase order :purchaseOrderNumber was close on :period
     */
    public function thePurchaseOrderWasClose(string $purchaseOrderNumber, string $period): void
    {
        $purchaseOrder = PurchaseOrder::where('number', $purchaseOrderNumber)->firstOrFail();

        app('purchaseOrder')->closePurchaseOrder($purchaseOrder);

        $purchaseOrder->closed_at = Carbon::parse($period);
        $purchaseOrder->save();
    }

    /**
     * @Then the purchase order :purchaseOrderNumber has a log entry by :name that reads
     */
    public function thePurchaseOrderHasALogEntryByThatReads(string $purchaseOrderNumber, string $name, PyStringNode $logEntry): void
    {
        $order = PurchaseOrder::where('number', $purchaseOrderNumber)->firstOrFail();
        $audit = $order->audits->first(fn (Audit $audit) => trim(strip_tags((string) $audit->custom_message)) == $logEntry);

        $this->assertNotNull($audit);
        $this->assertEquals($name, $audit->user->contactInformation->name);
    }

    /**
     * @When I receive :quantity of :sku into :locationName location
     */
    public function iReceiveOfInto($quantity, $sku, $locationName)
    {
        $this->iReceiveOfWithLotIntoLocation($quantity, $sku, null, $locationName);
    }

    /**
     * @When I receive :quantity of :sku with lot :lotName into :locationName location
     */
    public function iReceiveOfWithLotIntoLocation($quantity, $sku, $lotName, $locationName)
    {
        $location = $this->purchaseOrderInScope->warehouse->locations()
            ->where('name', $locationName)
            ->firstOrFail();

        $product = $this->purchaseOrderInScope->customer->products()
            ->where('sku', $sku)
            ->firstOrFail();

        if (method_exists($this, 'setProductInScope')) {
            $this->setProductInScope($product);
        }

        $purchaseOrderItem = $this->purchaseOrderInScope->purchaseOrderItems()
            ->where('product_id', $product->id)
            ->firstOrFail();

        // TODO: so frikkin stupid!
        $receiveRequestData = [
            'location_id' => [$purchaseOrderItem->id => $location->id],
            'lot_tracking' => [$purchaseOrderItem->id => $product->lot_tracking],
            'quantity_received' => [$purchaseOrderItem->id => $quantity],
            'product_id' => [$purchaseOrderItem->id => $product->id],
        ];

        if ($lotName) {
            $lot = $product->lots()->where('name', $lotName)->firstOrFail();

            $receiveRequestData['lot_id'] = [$purchaseOrderItem->id => $lot->id];
        }

        $receiveRequest = ReceivePurchaseOrderRequest::make($receiveRequestData);

        app('purchaseOrder')->updatePurchaseOrder($receiveRequest, $this->purchaseOrderInScope, false);
    }

    /**
     * @Then I shouldn't be able to receive :quantity of :sku into :locationName location
     */
    public function iShouldnTBeAbleToReceiveOfIntoLocation($quantity, $sku, $locationName)
    {
        $this->iShouldnTBeAbleToReceiveOfWithLotIntoLocation($quantity, $sku, null, $locationName);
    }

    /**
     * @Then I shouldn't be able to receive :quantity of :sku with lot :lotName into :locationName location
     */
    public function iShouldnTBeAbleToReceiveOfWithLotIntoLocation($quantity, $sku, $lotName, $locationName)
    {
        // TODO: figure out why this doesn't work
        // $this->expectException(ValidationException::class);
        // $this->iReceiveOfWithLotIntoLocation($quantity, $sku, $lotName, $locationName);
        // workaround:

        $thrownException = null;

        try {
            $this->iReceiveOfWithLotIntoLocation($quantity, $sku, $lotName, $locationName);
        } catch (Exception $exception) {
            $thrownException = $exception;
        }

        $this->assertInstanceOf(ValidationException::class, $thrownException);
    }

    /**
     * @Then the purchase order :purchaseOrderNumber should have these tags
     */
    public function thePurchaseOrderShouldHaveTheseTags(string $purchaseOrderNumber, TableNode $tagsTable): void
    {
        $purchaseOrder = PurchaseOrder::where('number', $purchaseOrderNumber)->firstOrFail();
        $expectedTags = $tagsTable->getRow(0);
        sort($expectedTags);
        $actualTags = $purchaseOrder->tags->pluck('name')->toArray();
        sort($actualTags);

        $this->assertEquals($expectedTags, $actualTags);
    }

    /**
     * @Then the purchase order :purchaseOrderNumber item :sku should contains in field :field a value :value
     */
    public function thePurchaseOrderItemShouldContainsInFieldAValue(string $purchaseOrderNumber,
                                                                    string $sku,
                                                                    string $field,
                                                                    string $value): void
    {
        $purchaseOrder = PurchaseOrder::where('number', $purchaseOrderNumber)->firstOrFail();
        $lineItem = $purchaseOrder->purchaseOrderItems->first(fn (PurchaseOrderItem $lineItem) => $lineItem->product->sku == $sku);

        $this->assertEquals($lineItem->{$field}, $value);
    }

    /**
     * @Then the unique charge document for order number :orderNumber with total charge :total_charge and quantity :quantity is generated
     */
    public function cargeDocumentForOrderNumberWithTotalAmountGenerated($orderNumber, $total_charge, $quantity): void
    {
        $chargeDocument = PurchaseOrderChargeCacheDocument::whereRaw(['purchase_order_number'=> $orderNumber]);
        $this->assertEquals($chargeDocument->count(), 1);
        $chargeDocument = $chargeDocument->first();

        $this->assertEquals($chargeDocument->charge['quantity'], $quantity);
        $this->assertEquals($chargeDocument->charge['total_charge'], $total_charge);
    }

    /**
     * @Then :documentCount purchase order cache document for order number :orderNumber was generated
     */
    public function purchaseOrderCacheDocumentForOrderNumberWasGenerated($documentCount, $orderNumber): void
    {
        $cacheDocument = PurchaseOrderCacheDocument::whereRaw(['purchase_order_number'=> $orderNumber]);
        $this->purchaseOrders = $cacheDocument->get();
        $this->assertEquals($cacheDocument->count(), $documentCount);
    }


    /**
     * @Then the purchase order charge cache document for order number :orderNumber with :quantity_items items is generated
     */
    public function billingDocumentForOrderNumberWithItemsGenerated($orderNumber, $quantity_items): void
    {
        $billingDocument = PurchaseOrderChargeCacheDocument::whereRaw(['purchase_order_number'=> $orderNumber]);
        $this->assertEquals($billingDocument->count(), 1);
        $billingDocument = $billingDocument->first();
        $this->assertEquals(count($billingDocument->items),$quantity_items);
    }
    /**
     * @Then the purchase order cache document for order number :orderNumber with :quantity_items items is generated
     */
    public function purchaseOrderCacheNumberWithItemsGenerated($orderNumber, $quantity_items): void
    {
        $billingDocument = PurchaseOrderCacheDocument::whereRaw(['purchase_order_number'=> $orderNumber]);
        $this->assertEquals($billingDocument->count(), 1);
        $billingDocument = $billingDocument->first();
        $this->assertEquals(count($billingDocument->items),$quantity_items);
    }

    /**
     * @Then there is no purchase order cache document for order number :orderNumber
     */
    public function thereIsNoBillingDocumentForOrderNumber($orderNumber): void
    {
        $billingDocument = PurchaseOrderChargeCacheDocument::whereRaw(['purchase_order_number'=> $orderNumber]);
        $this->assertEquals($billingDocument->count(), 0);
    }

    /**
     * @Then :documentCount purchase order charge document for order number :orderNumber was generated
     */
    public function theUniqueBillingDocumentForOrderNumberWasGenerated($documentCount, $orderNumber)
    {
        $chargeDocument = PurchaseOrderChargeCacheDocument::whereRaw(['purchase_order_number'=> $orderNumber]);
        $this->assertEquals($chargeDocument->count(), $documentCount);
    }



    /**
     * @Then purchase order cache document contains :billingRateCount billing rate with :chargeQuantity as quantity charge
     */
    public function purchaseOrderCacheDocumentContainsBillingRateWithAsQuantityCharge($billingRateCount, $chargeQuantity): void
    {
        $doc = PurchaseOrderCacheDocument::all();

        $filterPurchaseOrder = $doc->filter(function($element) use($billingRateCount){
            return count($element['calculated_billing_rates']) == $billingRateCount;
        });

        $filterPurchaseOrder = $filterPurchaseOrder->filter(function($purchaseOrder) use($chargeQuantity){
            $result = false;
            foreach ($purchaseOrder['calculated_billing_rates'] as $element){
                $result = $element['charges'] == (int)$chargeQuantity;
                if($result){
                    break;
                }
            }
            return $result;
        });

        $this->assertTrue($filterPurchaseOrder->isNotEmpty());
    }

    /**
     * @Given the customer has a purchase order with tracking number :purchaseOrderNumber for the warehouse :warehouseName
     */
    public function theCustomerHasAPurchaseOrderWithTrackingNumber(string $purchaseOrderNumber, string $warehouseName): void
    {
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
            $query->where('name', $warehouseName);
        })->firstOrFail();
        $this->purchaseOrderInScope = PurchaseOrder::create([
            'customer_id' => $customer->id,
            'number' => $purchaseOrderNumber,
            'warehouse_id' => $warehouse->id
        ]);
    }

    /**
     * @Given the purchase order has a product :sku with quantity :quantity
     */
    public function thePurchaseOrderHasAProductWithQuantity(string $sku, string $quantity): void
    {
        $product = Product::where('sku', $sku)->firstOrFail();
        $this->purchaseOrderInScope->purchaseOrderItems()->create([
            'product_id' => $product->id,
            'quantity' => $quantity
        ]);

        $this->purchaseOrderInScope->refresh();
    }

    /**
     * @When the customer starts editing the purchase order
     */
    public function theCustomerStartsEditingThePurchaseOrder(): void
    {
        $customer = $this->getCustomerInScope();
        $this->purchaseOrderUpdateRequestData = [
            'customer_id' => $customer->id,
            'warehouse_id' => $this->purchaseOrderInScope->warehouse_id,
            'supplier_id' => '',
            'number' => $this->purchaseOrderInScope->number,
            'tracking_number' => '',
            'tracking_url' => '',
            'ordered_at' => '',
            'expected_at' => '',
            'purchase_order_items' => $this->purchaseOrderInScope->purchaseOrderItems->map(function (PurchaseOrderItem $item) {
                return [
                    'purchase_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'quantity_sell_ahead' => $item->quantity_sell_ahead,
                    'quantity_received' => $item->quantity_received,
                ];
            })->toArray(),
        ];
    }

    /**
     * @Given the customer adds a product :sku with quantity :quantity to the purchase order
     */
    public function theCustomerAddsAProductWithQuantityToThePurchaseOrder(string $sku, string $quantity): void
    {
        $product = Product::where('sku', $sku)->firstOrFail();

        $this->purchaseOrderUpdateRequestData['purchase_order_items'][] = [
            'product_id' => $product->id,
            'quantity' => $quantity
        ];
    }

    /**
     * @Given the customer changes the quantity of the product :sku to :quantity
     */
    public function theCustomerChangesTheQuantityOfTheProductTo(string $sku, string $quantity): void
    {
        $product = Product::where('sku', $sku)->firstOrFail();

        // Find the sku in purchaseOrderUpdateRequestData and update the quantity
        foreach ($this->purchaseOrderUpdateRequestData['purchase_order_items'] as &$item) {
            if ($item['product_id'] == $product->id) {
                $item['quantity'] = $quantity;
            }
        }
    }

    /**
     * @When the customer changes the quantity received of the product :sku to :quantity
     */
    public function theCustomerChangesTheQuantityReceivedOfTheProductTo(string $sku, string $quantity): void
    {
        $product = Product::where('sku', $sku)->firstOrFail();

        // Find the sku in purchaseOrderUpdateRequestData and update the quantity
        foreach ($this->purchaseOrderUpdateRequestData['purchase_order_items'] as &$item) {
            if ($item['product_id'] == $product->id) {
                $item['quantity_received'] = $quantity;
            }
        }
    }

    /**
     * @When the customer changes the sell ahead quantity of the product :sku to :quantity
     */
    public function theCustomerChangesTheSellAheadQuantityReceivedOfTheProductTo(string $sku, string $quantity): void
    {
        $product = Product::where('sku', $sku)->firstOrFail();

        // Find the sku in purchaseOrderUpdateRequestData and update the quantity
        foreach ($this->purchaseOrderUpdateRequestData['purchase_order_items'] as &$item) {
            if ($item['product_id'] == $product->id) {
                $item['quantity_sell_ahead'] = $quantity;
            }
        }
    }

    /**
     * @When the customer finishes editing the purchase order
     */
    public function theCustomerFinishesEditingThePurchaseOrder(): void
    {
        $response = $this->putJson(route('purchase_orders.update', [
            'purchase_order' => $this->purchaseOrderInScope->id
        ]), $this->purchaseOrderUpdateRequestData);

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }

    /**
     * @Then the purchase order should have the product :sku with quantity :quantity
     */
    public function thePurchaseOrderShouldHaveTheProductWithQuantity(string $sku, string $quantity): void
    {
        $product = Product::where('sku', $sku)->firstOrFail();
        $purchaseOrderItem = $this->purchaseOrderInScope->purchaseOrderItems()->where('product_id', $product->id)->firstOrFail();

        $this->assertEquals($quantity, $purchaseOrderItem->quantity);
    }
}
