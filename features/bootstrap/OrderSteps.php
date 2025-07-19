<?php

use App\Components\{BulkShipComponent, OrderComponent, PackingComponent, UserComponent};
use App\Enums\Source;
use App\Models\{Audit,
    BulkShipBatch,
    BulkShipBatchOrder,
    Customer,
    KitItem,
    Printer,
    Product,
    Order,
    OrderChannel,
    OrderItem,
    ShipmentItem,
    ShippingBox,
    ShippingMethod,
    Tag,
    Currency,
    Return_,
    Warehouse};
use App\Models\Automations\TimeUnit;
use Behat\Gherkin\Node\TableNode;
use Illuminate\Database\Eloquent\Builder;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\{DB, App, Queue, Session};
use App\Http\Requests\Order\UpdateRequest as OrderUpdateRequest;
use App\Http\Requests\Shipment\ReShipRequest as OrderReShipRequest;
use App\Http\Requests\Order\StoreRequest as OrderStoreRequest;
use App\Http\Requests\Order\StoreReturnRequest as StoreOrderReturnRequest;
use App\Http\Requests\Order\StoreBatchRequest as OrderStoreBatchRequest;

/**
 * Behat steps to test customers.
 */
trait OrderSteps
{
    protected array $requestData = [];

    private ?Audit $logInScope = null;

    /**
     * @Given the shipment of order :orderNumber has a label with tracking :trackingNumber which cost :labelCost
     */
    public function theShipmentOfOrderHasALabelWithTrackingWhichCost(string $orderNumber, string $trackingNumber, string $labelCost)
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();
        $shipment = $order->shipments->first();
        $shipment->cost = $labelCost;
        $shipmentTracking = $shipment->shipmentTrackings->first();
        $shipmentTracking->tracking_number = $trackingNumber;
        DB::transaction(fn () => $shipmentTracking->save() and $shipment->save());
    }

    /**
     * @Given the order :orderNumber of client :customerName is tagged as :tagName
     */
    public function theOrderOfClientIsTaggedAs(string $orderNumber, string $customerName, string $tagName)
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $order = Order::where(['customer_id' => $customer->id, 'number' => $orderNumber])->firstOrFail();
        $tag = Tag::create(['customer_id' => $customer->id, 'name' => $tagName]);
        $order->tags()->attach($tag);
        $order->save();
    }

    /**
     * @Given the order :orderNumber of client :customerName is also tagged as :tagName
     */
    public function theOrderOfClientIsAlsoTaggedAs(string $orderNumber, string $customerName, string $tagName)
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $order = Order::where(['customer_id' => $customer->id, 'number' => $orderNumber])->firstOrFail();
        $tag = Tag::firstOrCreate(['customer_id' => $customer->id, 'name' => $tagName]);
        $order->tags()->attach($tag);
        $order->save();
    }

    /**
     * @Given the order :orderNumber requires :quantity units of SKU :sku
     */
    public function theOrderRequiresUnitsOfSku(string $orderNumber, string $quantity, string $sku): void
    {
        $orderQuery = Order::where('number', $orderNumber);
        $productQuery = Product::where('sku', $sku);

        if (method_exists($this, 'setCustomerInScope')) {
            $orderQuery->where('customer_id', $this->getCustomerInScope()->id);
            $productQuery->where('customer_id', $this->getCustomerInScope()->id);
        }

        $order = $orderQuery->firstOrFail();
        $product = $productQuery->firstOrFail();
        if($product->type == Product::PRODUCT_TYPE_STATIC_KIT){
            foreach ($product->kitItems as $item) {

                $data = [
                    'product_id' => $item->id,
                    'quantity' => $quantity,
                    'quantity_shipped' => 0
                ];

                OrderItem::factory()->for($order)->create($data);
            }
        }else{

            $data = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'quantity_shipped' => 0
            ];


            OrderItem::factory()->for($order)->create($data);
        }
    }

    /**
     * @Given the order :orderNumber requires :quantity units of SKU :sku for real
     */
    public function theOrderRequiresUnitsOfSkuForReal(string $orderNumber, string $quantity, string $sku): void
    {
        $orderQuery = Order::where('number', $orderNumber);
        $productQuery = Product::where('sku', $sku);

        if (method_exists($this, 'setCustomerInScope')) {
            $orderQuery->where('customer_id', $this->getCustomerInScope()->id);
            $productQuery->where('customer_id', $this->getCustomerInScope()->id);
        }

        $order = $orderQuery->firstOrFail();

        App::make('order')->updateOrderItems($order, [[
            'product_id' => $productQuery->firstOrFail()->id,
            'quantity' => $quantity
        ]]);
    }

    /**
     * @Given the order number :orderNumber was created :timeValue :timeUnit ago
     */
    public function theOrderNumberWasCreatedAgo(string $orderNumber, string $timeValue, string $timeUnit): void
    {
        $customer = $this->getCustomerInScope();
        $order = Order::firstOrCreate(['customer_id' => $customer->id, 'number' => $orderNumber]);
        $order->created_at = $timeUnit == TimeUnit::BUSINESS_DAYS->value
            ? sub_business_days(now(), $timeValue)
            : now()->{'sub' . ucfirst($timeUnit)}($timeValue);
        $order->save();
    }

    /**
     * @Given the customer :customerName got the order number :orderNumber for :quantity SKU :sku
     */
    public function theCustomerGotTheOrderNumberForSku(string $customerName, string $orderNumber, string $quantity, string $sku): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $product = Product::where(['customer_id' => $customer->id, 'sku' => $sku])->firstOrFail();

        $formRequest = OrderStoreRequest::make([
            'customer_id' => $product->customer->id,
            'number' => $orderNumber,
            'order_items' => [[
                'product_id' => $product->id,
                'sku' => $product->sku,
                'quantity' => $quantity
            ]]
        ]);

        $order = App::make('order')->store($formRequest, false);

        $order->priority = false;
        $order->save();

        if (method_exists($this, 'setCustomerInScope')) {
            $this->setCustomerInScope($customer);
        }
    }

    /**
     * @Given the line SKU :sku on the order :orderNumber is cancelled
     */
    public function theLineSKUOnTheOrderGetCancelled(string $sku, string $orderNumber)
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();
        $lineItem = $order->orderItems->first(fn (OrderItem $lineItem) => $lineItem->sku == $sku);
        app('order')->cancelOrderItem($lineItem);
    }

    /**
     * @When the customer :customerName gets the order number :orderNumber for these SKUs
     */
    public function theCustomerGetsTheOrderNumberForTheseSkus(
        string $customerName, string $orderNumber, TableNode $itemsTable
    ): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $formRequest = OrderStoreRequest::make([
            'customer_id' => $customer->id,
            'number' => $orderNumber,
            'order_items' => collect($itemsTable->getRows())->map(function (array $row) use ($customer) {
                [$quantity, $sku] = $row;
                $product = Product::where(['customer_id' => $customer->id, 'sku' => $sku])->firstOrFail();

                $price = $row[2] ?? null;
                if ($price) {
                    return ['product_id' => $product->id, 'sku' => $product->sku, 'quantity' => $quantity, 'price' => $price];
                }

                return ['product_id' => $product->id, 'sku' => $product->sku, 'quantity' => $quantity];
            })->toArray()
        ]);

        $order = App::make('order')->store($formRequest, false);

        $order->priority = false;
        $order->save();

        if (method_exists($this, 'setCustomerInScope')) {
            $this->setCustomerInScope($customer);
        }
    }

    /**
     * @Then the order :orderNumber line item :sku must have customs price of :price
     */
    public function theOrderLineItemMustHaveThePriceOf(string $orderNumber, string $sku, float $price): void
    {
        $product = $this->getCustomerInScope()->products()->where('sku', $sku)->firstOrFail();
        $lineItem = OrderItem::query()
            ->whereHas('order', fn (Builder $query) => $query->where('number', $orderNumber))
            ->where('product_id', $product->id)
            ->firstOrFail();

        $this->assertEquals($price, $lineItem->priceForCustoms());
    }

    /**
     * @Then the total price of all kit components with SKU :sku in order :orderNumber must be :price
     */
    public function theSumOfTheOrderKitSkuComponentLinesMustBe(string $orderNumber, string $sku, float $price): void
    {
        $customer = $this->getCustomerInScope();
        $order = $customer->orders()->where('number', $orderNumber)->firstOrFail();
        $product = $customer->products()->where('sku', $sku)->firstOrFail();
        $kitComponents = $order->orderItems->where('product_id', $product->id)->first()->kitOrderItems;

        $priceForCustomsSum = number_format($kitComponents->sum(function (OrderItem $item) {
            $quantityInKit = KitItem::query()
                ->where('child_product_id', $item->product->id)
                ->where('parent_product_id', $item->parentOrderItem->product->id)
                ->first()
                ->quantity;

            return $item->priceForCustoms() * $quantityInKit;
        }), 2);

        $this->assertEquals($price, $priceForCustomsSum);
    }

    /**
     * @When the channel :channelName gets the order number :orderNumber for :quantity SKU :sku
     */
    public function theChannelGetsTheOrderNumber(string $channelName, string $orderNumber, string $quantity, string $sku): void
    {
        $channel = OrderChannel::where('name', $channelName)->firstOrFail();
        $product = Product::where('sku', $sku)->firstOrFail();
        $formRequest = OrderStoreRequest::make([
            'customer_id' => $channel->customer->id,
            'order_channel_id' => $channel->id,
            'number' => $orderNumber,
            'order_items' => [[
                'product_id' => $product->id,
                'sku' => $product->sku,
                'quantity' => $quantity
            ]],
            // Non-international.
            'shipping_contact_information' => ['country_id' => $channel->customer->contactInformation->country->id]
        ]);

        App::make('order')->store($formRequest, false);
    }

    /**
     * @When the channel :channelName gets the order number :orderNumber with these tags
     */
    public function theChannelGetsTheOrderNumberWithTheseTags(string $channelName, string $orderNumber, TableNode $tagsTable): void
    {
        $channel = OrderChannel::where('name', $channelName)->firstOrFail();
        $product = Product::factory()->create(['customer_id' => $channel->customer_id]);
        $formRequest = OrderStoreRequest::make([
            'customer_id' => $channel->customer->id,
            'order_channel_id' => $channel->id,
            'number' => $orderNumber,
            'order_items' => [[
                'product_id' => $product->id,
                'sku' => $product->sku,
                'quantity' => 1
            ]],
            'tags' => $tagsTable->getRow(0),
            // Non-international.
            'shipping_contact_information' => ['country_id' => $channel->customer->contactInformation->country->id]
        ]);

        App::make('order')->store($formRequest, false);
    }

    /**
     * @When the channel :channelName gets order :orderNumber with flag :flagName toggled :flagValue
     */
    public function theChannelGetsOrderWithFlagToggled(
        string $channelName, string $orderNumber, string $flagName, string $flagValue
    ): void
    {
        $channel = OrderChannel::where('name', $channelName)->firstOrFail();
        $product = Product::factory()->create(['customer_id' => $channel->customer_id]);
        $formRequest = OrderStoreRequest::make([
            'customer_id' => $channel->customer->id,
            'order_channel_id' => $channel->id,
            'number' => $orderNumber,
            'order_items' => [[
                'product_id' => $product->id,
                'sku' => $product->sku,
                'quantity' => 1
            ]],
            $flagName => self::onOrOffToBool($flagValue),
            // Non-international.
            'shipping_contact_information' => ['country_id' => $channel->customer->contactInformation->country->id]
        ]);

        App::make('order')->store($formRequest, false);
    }

    /**
     * @When the customer :customerName gets order :orderNumber with flag :flagName toggled :flagValue for these SKUs
     */
    public function theCustomerGetsOrderWithFlagToggledForTheseSkus(
        string $customerName, string $orderNumber, string $flagName, string $flagValue,  TableNode $itemsTable
    ): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $formRequest = OrderStoreRequest::make([
            'customer_id' => $customer->id,
            'number' => $orderNumber,
            'order_items' => collect($itemsTable->getRows())->map(function (array $row) use ($customer) {
                [$quantity, $sku] = $row;
                $product = Product::where(['customer_id' => $customer->id, 'sku' => $sku])->firstOrFail();

                return ['product_id' => $product->id, 'sku' => $product->sku, 'quantity' => $quantity];
            })->toArray(),
            $flagName => self::onOrOffToBool($flagValue),
            // Non-international.
            'shipping_contact_information' => ['country_id' => $customer->contactInformation->country->id]
        ]);

        $order = App::make('order')->store($formRequest, false);

        $order->priority = false;
        $order->save();

        if (method_exists($this, 'setCustomerInScope')) {
            $this->setCustomerInScope($customer);
        }
    }

    /**
     * @When the channel :channelName gets the order number :orderNumber with shipping method name :shippingMethodName
     */
    public function theChannelGetsTheOrderNumberWithShippingMethodName(
        string $channelName, string $orderNumber, string $shippingMethodName
    ): void
    {
        $channel = OrderChannel::where('name', $channelName)->firstOrFail();
        $product = Product::factory()->create(['customer_id' => $channel->customer_id]);
        $formRequest = OrderStoreRequest::make([
            'customer_id' => $channel->customer->id,
            'order_channel_id' => $channel->id,
            'number' => $orderNumber,
            'order_items' => [[
                'product_id' => $product->id,
                'sku' => $product->sku,
                'quantity' => 1
            ]],
            'shipping_method_name' => $shippingMethodName,
            // Non-international.
            'shipping_contact_information' => ['country_id' => $channel->customer->contactInformation->country->id]
        ]);
        App::make('order')->store($formRequest, false);
    }

    /**
     * @When an order with the number :orderNumber for :quantity SKU :sku is created
     */
    public function anOrderWithTheNumberForOneSkuIsCreated(string $orderNumber, string $quantity, string $sku): void
    {
        $customerContactInformation = $this->getCustomerInScope()->contactInformation;

        $product = Product::where('sku', $sku)->firstOrFail();
        $formRequest = OrderStoreRequest::make([
            'customer_id' => $product->customer->id,
            'number' => $orderNumber,
            'currency_id' => Currency::all()->first()->id ?? null,
            'shipping_contact_information' => [
                'zip' => $customerContactInformation->zip,
                'country_id' => $customerContactInformation->country_id,
                'city' => $customerContactInformation->city,
                'email' => $customerContactInformation->email,
                'phone' => $customerContactInformation->phone
            ],
            'order_items' => [[
                'product_id' => $product->id,
                'sku' => $product->sku,
                'quantity' => $quantity
            ]]
        ]);

        App::make('order')->store($formRequest, false);
    }

    /**
     * @When an order with the number :orderNumber for :quantity SKU :sku is created with fulfilled status
     */
    public function anOrderWithTheNumberForOneSkuIsCreatedWithFulfilledStatus(string $orderNumber, string $quantity, string $sku): void
    {
        $customerContactInformation = $this->getCustomerInScope()->contactInformation;

        $product = Product::where('sku', $sku)->firstOrFail();
        $formRequest = OrderStoreRequest::make([
            'customer_id' => $product->customer->id,
            'number' => $orderNumber,
            'currency_id' => Currency::all()->first()->id ?? null,
            'shipping_contact_information' => [
                'zip' => $customerContactInformation->zip,
                'country_id' => $customerContactInformation->country_id,
                'city' => $customerContactInformation->city,
                'email' => $customerContactInformation->email,
                'phone' => $customerContactInformation->phone
            ],
            'order_items' => [[
                'product_id' => $product->id,
                'sku' => $product->sku,
                'quantity' => $quantity,
                'quantity_pending' => 0,
                'quantity_shipped' => $quantity
            ]]
        ]);

        App::make(OrderComponent::class)->store($formRequest, false);
    }

    /**
     * @When an order with the number :orderNumber for :quantity SKU :sku is created with cancelled status
     */
    public function anOrderWithTheNumberForOneSkuIsCreatedWithCancelledStatus(string $orderNumber, string $quantity, string $sku): void
    {
        $customerContactInformation = $this->getCustomerInScope()->contactInformation;

        $product = Product::where('sku', $sku)->firstOrFail();
        $formRequest = OrderStoreRequest::make([
            'customer_id' => $product->customer->id,
            'number' => $orderNumber,
            'currency_id' => Currency::all()->first()->id ?? null,
            'shipping_contact_information' => [
                'zip' => $customerContactInformation->zip,
                'country_id' => $customerContactInformation->country_id,
                'city' => $customerContactInformation->city,
                'email' => $customerContactInformation->email,
                'phone' => $customerContactInformation->phone
            ],
            'order_items' => [[
                'product_id' => $product->id,
                'sku' => $product->sku,
                'quantity' => 0,
                'quantity_pending' => 0,
                'quantity_shipped' => 0,
            ]]
        ]);

        App::make(OrderComponent::class)->store($formRequest, false);
    }

    /**
     * @When an order with the number :orderNumber for :quantity SKU :sku is created by source :source
     */
    public function anOrderWithTheNumberForOneSkuIsCreatedBySource(string $orderNumber, string $quantity, string $sku, string $source): void
    {
        $customerContactInformation = $this->getCustomerInScope()->contactInformation;

        $product = Product::where('sku', $sku)->firstOrFail();
        $formRequest = OrderStoreRequest::make([
            'customer_id' => $product->customer->id,
            'number' => $orderNumber,
            'currency_id' => Currency::all()->first()->id ?? null,
            'shipping_contact_information' => [
                'zip' => $customerContactInformation->zip,
                'country_id' => $customerContactInformation->country_id,
                'city' => $customerContactInformation->city,
                'email' => $customerContactInformation->email,
                'phone' => $customerContactInformation->phone
            ],
            'order_items' => [[
                'product_id' => $product->id,
                'sku' => $product->sku,
                'quantity' => $quantity
            ]]
        ]);

        App::make('order')->store($formRequest, false, Source::from($source));
    }

    /**
     * @When the order :orderNumber has :quantity of the SKU :sku added to it
     */
    public function theOrderHasTheSkuAddedToIt(string $orderNumber, string $quantity, string $sku): void
    {
        $customer = $this->getCustomerInScope();
        $order = Order::firstOrCreate(['customer_id' => $customer->id, 'number' => $orderNumber]);
        $product = Product::where('sku', $sku)->firstOrFail();
        $formRequest = OrderUpdateRequest::make([
            'customer_id' => $order->customer_id,
            'order_items' => [[
                'product_id' => $product->id,
                'sku' => $product->sku,
                'quantity' => (float) $quantity
            ]]
        ]);

        App::make('order')->update($formRequest, $order, false);
    }

    /**
     * @When the order :orderNumber has it's shipping method set to :shippingMethodName from carrier :carrierName
     */
    public function theOrderHasItsShippingMethodSetTo(string $orderNumber, string $shippingMethodName, string $carrierName): void
    {
        $customer = $this->getCustomerInScope();
        $order = Order::where(['customer_id' => $customer->id, 'number' => $orderNumber])->firstOrFail();
        $shippingMethod = $customer->shippingMethods()
            ->whereHas('shippingCarrier', fn (Builder $query) => $query->where('shipping_carriers.name', $carrierName))
            ->where('shipping_methods.name', $shippingMethodName)
            ->firstOrFail();
        $formRequest = OrderUpdateRequest::make([
            'customer_id' => $order->customer_id,
            'shipping_method_id' => $shippingMethod->id
        ]);

        App::make('order')->update($formRequest, $order, false);
    }

    /**
     * @When the order :orderNumber has :quantity of the SKU :sku added to it by :customerName
     */
    public function theOrderHasTheSkuAddedToItBy(string $orderNumber, string $quantity, string $sku, string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $order = Order::firstOrCreate(['customer_id' => $customer->id, 'number' => $orderNumber]);
        $product = Product::where('sku', $sku)->firstOrFail();
        $formRequest = OrderUpdateRequest::make([
            'customer_id' => $order->customer_id,
            'order_items' => [[
                'product_id' => $product->id,
                'sku' => $product->sku,
                'quantity' => (float) $quantity
            ]]
        ]);

        App::make('order')->update($formRequest, $order, false);
    }

    /**
     * @When the order :orderNumber has :quantity of the SKU :sku added to it by :customerName by source :source
     */
    public function theOrderHasTheSkuAddedToItByBySource(string $orderNumber, string $quantity, string $sku, string $customerName, string $source): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $order = Order::firstOrCreate(['customer_id' => $customer->id, 'number' => $orderNumber]);
        $product = Product::where('sku', $sku)->firstOrFail();
        $formRequest = OrderUpdateRequest::make([
            'customer_id' => $order->customer_id,
            'order_items' => [[
                'product_id' => $product->id,
                'sku' => $product->sku,
                'quantity' => (float) $quantity
            ]]
        ]);

        App::make('order')->update($formRequest, $order, false, Source::from($source));
    }

    /**
     * @When the order :orderNumber sets allow partial to :allowPartial
     */
    public function theOrderSetsAllowPartialTo(string $orderNumber, string $allowPartial): void
    {
        $customer = $this->getCustomerInScope();
        $order = Order::where(['customer_id' => $customer->id, 'number' => $orderNumber])->firstOrFail();
        $formRequest = OrderUpdateRequest::make([
            'allow_partial' => (int) self::onOrOffToBool($allowPartial)
        ]);

        App::make('order')->update($formRequest, $order, false);

        $this->order = $order->refresh();
    }

    /**
     * @When the order :orderNumber is marked as fulfilled
     */
    public function theOrderIsMarkedAsFulfilled($orderNumber): void
    {
        $customer = $this->getCustomerInScope();
        $order = Order::where(['customer_id' => $customer->id, 'number' => $orderNumber])->firstOrFail();
        app('order')->markAsFulfilled($order);
    }

    /**
     * @Then the order :orderNumber with line sku :sku should be kit and have :count component lines
     */
    public function theOrderWithLineSkuShouldBeKitAndHaveKitItems(string $orderNumber, string $sku, $count): void
    {
        $customer = $this->getCustomerInScope();

        $order = Order::where(['customer_id' => $customer->id, 'number' => $orderNumber])->firstOrFail();
        $lineItem = $order->orderItems->first(fn (OrderItem $lineItem) => $lineItem->sku == $sku);

        $this->assertTrue($lineItem->product->isKit());
        $this->assertEquals($count, $lineItem->kitOrderItems->count());
    }

    /**
     * @Then the order :orderNumber should have the following packing note
     */
    public function theOrderShouldHaveTheFollowingPackingNote(string $orderNumber, PyStringNode $note): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();

        $this->assertEquals($note, $order->packing_note);
    }

    /**
     * @Then the order :orderNumber should have the shipping carrier :carrierName and the shipping method :methodName
     */
    public function theOrderShouldHaveTheShippingCarrierAndTheShippingMethod(string $orderNumber, string $carrierName, string $methodName): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();

        $this->assertNotNull($order->shippingMethod);
        $this->assertEquals($methodName, $order->shippingMethod->name);
        $this->assertEquals($carrierName, $order->shippingMethod->shippingCarrier->name);
    }

    /**
     * @Then the order :orderNumber should have a line item with :quantity of the SKU :sku
     */
    public function theOrderShouldHaveALineItemWithOfTheSku(string $orderNumber, string $quantity, string $sku): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();
        $lineItem = $order->orderItems->first(fn (OrderItem $lineItem) => $lineItem->sku == $sku);

        $this->assertNotNull($lineItem);
        $this->assertEquals($quantity, $lineItem->quantity);
    }

    /**
     * @Then the order :orderNumber should not have a line item with the SKU :sku
     */
    public function theOrderShouldNotHaveALineItemWithTheSku(string $orderNumber, string $sku): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();
        $lineItem = $order->orderItems->first(fn (OrderItem $lineItem) => $lineItem->sku == $sku);

        $this->assertNull($lineItem);
    }

    /**
     * @Then the order :orderNumber should have these tags
     */
    public function theOrderShouldHaveTheseTags(string $orderNumber, TableNode $tagsTable): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();
        $expectedTags = $tagsTable->getRow(0);
        sort($expectedTags);
        $actualTags = $order->tags->pluck('name')->toArray();
        sort($actualTags);

        $this->assertEquals($expectedTags, $actualTags);
    }

    /**
     * @Then the order :orderNumber should not have these tags
     */
    public function theOrderShouldNotHaveTheseTags(string $orderNumber, TableNode $tagsTable): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();
        $expectedTags = $tagsTable->getRow(0);
        sort($expectedTags);
        $actualTags = $order->tags->pluck('name')->toArray();
        sort($actualTags);

        $this->assertNotEquals($expectedTags, $actualTags);
    }

    /**
     * @Then the order :orderNumber should have the :flagName set to :flagValue
     */
    public function theOrderShouldHaveTheSetTo(string $orderNumber, string $flagName, string $flagValue): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();

        $this->assertEquals(self::onOrOffToBool($flagValue), $order->$flagName);
    }

    /**
     * @Then the order :orderNumber should have the field :fieldName set to :fieldValue
     */
    public function theOrderShouldHaveTheFieldSetTo(string $orderNumber, string $fieldName, string $fieldValue): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();

        $this->assertEquals($fieldValue, get_nested_value($order, $fieldName));
    }

    /**
     * @Then the order :orderNumber should have empty product ID in order item SKU :sku
     */
    public function theOrderShouldHaveEmptyProductIdInOrderItemSku(string $orderNumber, string $sku): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();
        $lineItem = $order->orderItems->first(fn (OrderItem $lineItem) => $lineItem->sku == $sku);
        $this->assertNull($lineItem->product_id);
    }

    /**
     * @Then the order :orderNumber should have the product in order item SKU :sku with same details as product
     */
    public function theOrderShouldHaveTheProductInOrderItemSkuWithSameDetailsAsProduct(string $orderNumber, string $sku): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();
        $lineItem = $order->orderItems->first(fn (OrderItem $lineItem) => $lineItem->sku == $sku);

        $this->assertNotNull($lineItem->product_id);
        $this->assertSame($lineItem->product->name, $lineItem->name);
        $this->assertSame($lineItem->product->weight, $lineItem->weight);
        $this->assertSame($lineItem->product->length, $lineItem->length);
        $this->assertSame($lineItem->product->width, $lineItem->width);
        $this->assertSame($lineItem->product->height, $lineItem->height);

    }

    /**
     * @Then the line SKU :sku on the order :orderNumber should be allocated and pickable
     */
    public function theLineSkuOnTheOrderShouldBeAllocatedAndPickable(string $sku, string $orderNumber): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();
        $lineItem = $order->orderItems->first(fn (OrderItem $lineItem) => $lineItem->sku == $sku);
        $this->assertEquals($lineItem->quantity_allocated, $lineItem->quantity_pending);
        $this->assertEquals($lineItem->quantity_allocated_pickable, $lineItem->quantity_allocated);
    }

    /**
     * @Then the line SKU :sku on the order :orderNumber should have the following quantities
     */
    public function theLineSkuOnTheOrderShouldHaveTheFollowingQuantities(string $sku, string $orderNumber, TableNode $quantitiesTable): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();
        $lineItem = $order->orderItems->first(fn (OrderItem $lineItem) => $lineItem->sku == $sku);
        self::checkOrderLineQuantities($lineItem, $quantitiesTable->getRow(0), $quantitiesTable->getRow(1));
    }

    /**
     * @Then I select :sku to reship :quantity from the order :orderNumber
     */
    public function iSelectSkuToReshipQuantityFromTheOrder(string $sku, $quantity, string $orderNumber): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();
        $lineItem = $order->orderItems->first(fn (OrderItem $lineItem) => $lineItem->sku == $sku);

        // Instead of only adding one order_item, we need to append it to existing
//        Wrong example: $this->requestData = [
//            'order_items' => [[
//                'order_item_id' => (string) $lineItem->id,
//                'quantity' => $quantity,
//                'add_inventory' => (string) $lineItem->id
//            ]],
//            'reship_order_status_id' => null,
//            'customer_id' => $order->customer_id,
//        ];

        $orderItem = [
            'order_item_id' => (string) $lineItem->id,
            'quantity' => $quantity,
            'add_inventory' => (string) $lineItem->id
        ];

        $currentOrderItems = $this->requestData['order_items'] ?? [];

        $this->requestData = [
            'order_items' => [
                ...$currentOrderItems,
                $orderItem
            ],
            'reship_order_status_id' => null,
            'customer_id' => $order->customer_id,
        ];
    }

    /**
     * @Then I reship the order :orderNumber
     */
    public function iReshipTheOrder(string $orderNumber): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();
        $formRequest = OrderReShipRequest::make($this->requestData);

        App::make('order')->reshipOrderItems($order, $formRequest);

        $this->requestData = [];
    }

    /**
     * @Then I return order :orderNumber and choose order line SKU :sku from location :locationName with quantity :quantity
     */
    public function iReturnOrderAndChooseOrderLineSkuFromLocationWithQuantity(string $orderNumber,
                                                                  string $sku,
                                                                  string $locationName,
                                                                  $quantity): void
    {
        $warehouse = $this->getWarehouseInScope();
        $customer = $warehouse->customer;
        $location = $warehouse->locations()->where('name', $locationName)->firstOrFail();
        $order = Order::where(['number' => $orderNumber, 'customer_id' => $customer->id])->firstOrFail();
        $lineItem = $order->orderItems->first(fn (OrderItem $lineItem) => $lineItem->sku == $sku);

        $this->order = $order;

        $this->requestData = [
            "order_id" => $order->id,
            "customer_id" => $customer->id,
            "warehouse_id" => $warehouse->id,
            "return_status_id" => "pending",
            "printer_id" => null,
            "order_items" => [
                $lineItem->id => [
                    'quantity' => $quantity,
                    'is_returned' => "1",
                    'product_id' => $lineItem->product->id,
                    'location_id' => $location->id,
                    'tote_id' => null,
                    'order_item_id' => $lineItem->id
                ]
            ],
            "reason" => "Test text",
            "own_label" => "0"
        ];
    }

    /**
     * @Then I return the order using :shippingMethodName method
     */
    public function iReturnTheOrderUsingMethod(string $shippingMethodName): void
    {
        $customer = $this->getWarehouseInScope()->customer;

        $shippingMethod = $customer->shippingMethods()
            ->where('shipping_methods.name', $shippingMethodName)
            ->firstOrFail();

        $this->requestData['shipping_method_id'] = $shippingMethod?->id;

        $request = StoreOrderReturnRequest::make($this->requestData);

        app('return')->storeOrderReturn($this->order, $request);
    }

    /**
     * @Then I expect the order return to be successful, with a line containing SKU :sku and a returned quantity of :quantity
     */
    public function iExpectTheOrderReturnToBeSuccessful($sku, $quantity): void
    {
        $order = $this->order;

        $return = Return_::with(['returnItems', 'returnItems.orderItem'])->where('order_id', $order->id)->firstOrFail();
        $returnItem = $return->returnItems->first(fn (Product $product) => $product->sku == $sku);

        $this->assertNotNull($returnItem);
        $this->assertEquals($returnItem->pivot->quantity, $quantity);
    }

    /**
     * @Then I expect the order return label to include tracking links
     */
    public function iExpectTheOrderReturnLabelToIncludeTrackingLinks(): void
    {
        $order = $this->order;

        $return = Return_::with(['returnLabels', 'returnTrackings'])->where('order_id', $order->id)->firstOrFail();

        $this->assertTrue($return->returnLabels->count() > 0);
        $this->assertTrue($return->returnTrackings->count() > 0);
        $this->assertNotNull($return->returnLabels->first()->url);
        $this->assertNotNull($return->returnTrackings->first()->tracking_number);
        $this->assertNotNull($return->returnTrackings->first()->tracking_url);
    }

    private static function onOrOffToBool(string $onOrOff): bool
    {
        if ($onOrOff == 'on') {
            return true;
        } elseif ($onOrOff == 'off') {
            return false;
        } else {
            throw new PendingException('TODO: on/off was expected, "' . $onOrOff . '" was received.');
        }
    }

    /**
     * @Then the order :orderNumber has a log entry by :name that reads
     */
    public function theOrderHasALogEntryByThatReads(string $orderNumber, string $name, PyStringNode $logEntry): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();
        $this->logInScope = $order->audits->first(function (Audit $audit) use ($logEntry) {
            $custom_message = trim(strip_tags((string) $audit->custom_message));
            $log = $logEntry->getRaw();

            if ($audit->ranByAutomation) {
                return "Rule {$audit->ranByAutomation->name}: $custom_message" === $log;
            }

            return $custom_message === $log;
        });

        $this->assertNotNull($this->logInScope);
        $this->assertEquals($name, $this->logInScope->user->contactInformation->name);
    }

    /**
     * @Then the order :orderNumber should have the shipping box :boxName of customer :customerName
     */
    public function theOrderShouldHaveTheShippingBoxOfCustomer(string $orderNumber, string $boxName, string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $order = Order::where('number', $orderNumber)->firstOrFail();
        $box = ShippingBox::where(['customer_id' => $customer->id, 'name' => $boxName])->firstOrFail();

        $this->assertEquals($box->id, $order->shippingBox->id);
    }

    /**
     * @Then the order :orderNumber should be marked as fulfilled
     */
    public function theOrderShouldBeMarkedAsFulfilled(string $orderNumber): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();

        $this->assertNull($order->cancelled_at);
        $this->assertNotNull($order->fulfilled_at);
    }

    /**
     * @Then the order :orderNumber should not be marked as fulfilled
     */
    public function theOrderShouldNotBeMarkedAsFulfilled(string $orderNumber): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();

        $this->assertNull($order->cancelled_at);
        $this->assertNull($order->fulfilled_at);
    }

    /**
     * @Then the order :orderNumber should be cancelled
     */
    public function theOrderShouldBeCancelled(string $orderNumber): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();

        $this->assertNull($order->fulfilled_at);
        $this->assertNotNull($order->cancelled_at);
    }

    /**
     * @Then the order :orderNumber should not have tags
     */
    public function theOrderShouldNotHaveTags(string $orderNumber): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();

        $this->assertEmpty($order->tags->toArray());
    }

    /**
     * @Given the order :orderNumber is set to be shipped to :zip, :countryName
     */
    public function theOrderIsSetToBeShippedTo($orderNumber, $zip, $countryName)
    {
        $orderContactInformation = $this->getCustomerInScope()->orders()
            ->where('number', $orderNumber)
            ->firstOrFail()
            ->shippingContactInformation;

        $orderContactInformation->country_id = Countries::where('name', $countryName)->firstOrFail()->id;
        $orderContactInformation->zip = $zip;

        $orderContactInformation->save();
    }

    /**
     * @Then the order number :orderNumber has item count :countNumber
     */
    public function theOrderNumberHaveItemCount(string $orderNumber, $countNumber): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();

        $this->assertCount($countNumber, $order->orderItems);
    }

    /**
     * @Then the order number :orderNumber with kit order item SKU :sku has :countNumber component lines
     */
    public function theOrderNumberWithKitOrderItemSkuHasComponentLines(string $orderNumber, string $sku, $countNumber): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();

        $orderItem = OrderItem::where(['order_id' => $order->id, 'sku' => $sku])->firstOrFail();

        $this->assertCount($countNumber, $orderItem->kitOrderItems);
    }

    /**
     * @Then the order number :orderNumber should have SKU :sku with quantity :quantityNumber
     */
    public function theOrderNumberShouldHaveSkuWithQuantity(string $orderNumber, string $sku, string $quantityNumber): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();

        $orderItem = OrderItem::where(['order_id' => $order->id, 'sku' => $sku])->firstOrFail();

        $this->assertEquals($orderItem->quantity, $quantityNumber);
    }

    /**
     * @Then the order number :orderNumber should have cancelled SKU :sku with quantity :quantityNumber
     */
    public function theOrderNumberShouldHaveCancelledSKUWithQuantity(string $orderNumber, string $sku, string $quantityNumber): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();

        $orderItem = OrderItem::where(['order_id' => $order->id, 'sku' => $sku])->firstOrFail();

        $this->assertEquals($orderItem->quantity, $quantityNumber);
        $this->assertNotNull($orderItem->cancelled_at);
    }

    /**
     * @Then the order :orderNumber is flagged as wholesale
     */
    public function theOrderIsFlaggedAsWholesale(string $orderNumber): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();

        $this->assertTrue($order->is_wholesale);
    }

    /**
     * @param OrderItem $orderLine
     * @param array $fieldNames
     * @param array $fieldValues
     * @return void
     */
    public static function checkOrderLineQuantities(OrderItem $orderLine, array $fieldNames, array $fieldValues): void
    {
        foreach ($fieldNames as $key => $fieldName) {
            if ($fieldValues[$key] === 'null') {
                $fieldValues[$key] = null;
            }

            self::assertEquals($fieldValues[$key], $orderLine->{$fieldName}, "Failed asserting on field {$fieldName} that {$fieldValues[$key]} matches expected {$orderLine->{$fieldName}}");
        }
    }

    /**
     * @Given the order :orderNumber should be ready to ship
     */
    public function theOrderShouldBeReadyToShip($orderNumber): void
    {
        app('order')->recalculateReadyToShipOrders();

        $order = Order::whereNumber($orderNumber)->firstOrFail();

        $this->assertTrue((bool)$order->ready_to_ship);
    }

    /**
     * @Given I recalculate orders that are ready to ship
     */
    public function iRecalculateOrdersThatAreReadyToShip(): void
    {
        app('order')->recalculateReadyToShipOrders();
    }

    /**
     * @Then the order :orderNumber should have all of its items shipped
     */
    public function theOrderShouldHaveAllOfItsItemsShipped($orderNumber): void
    {
        $order = Order::whereNumber($orderNumber)->firstOrFail();

        $pendingItems = false;

        foreach ($order->orderItems as $orderItem) {
            if ($orderItem->quantity_pending > 0) {
                $pendingItems = true;
            }
        }

        $this->assertFalse($pendingItems);
    }

    /**
     * @Then the order line with SKU :sku from order :orderNumber has shipment items
     */
    public function theOrderLineWithSkuFromOrderHasShipmentItems($sku, $orderNumber): void
    {
        $order = Order::whereNumber($orderNumber)->firstOrFail();

        $orderItem = $order->orderItems()->where('sku', $sku)->firstOrFail();

        $shipmentItem = ShipmentItem::whereOrderItemId($orderItem->id)->first();

        $this->assertNotNull($shipmentItem);
    }

     /**
     * @Given the customer :customerName creates :numberOfOrders orders for :numberOfItems SKU :sku with shipping method from carrier :carrier set to :shippingMethod
     */
    public function theUserCreatesOrdersForSKUWithShippingMethodSetTo($customerName, $numberOfOrders, $numberOfItems, $sku, $carrier, $shippingMethod): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $product = Product::where(['customer_id' => $customer->id, 'sku' => $sku])->firstOrFail();

        $shippingMethod = ShippingMethod::whereHas('shippingCarrier', function (Builder $query) use ($carrier){
            $query->where('name', $carrier);
        })
            ->where('name', $shippingMethod)
            ->firstOrFail();

        $formRequests = [];

        for ($i = 1; $i <= $numberOfOrders; $i++) {
            // Check if ORD-{$i} already exists, if so, get the latest order number and increment it by 1
            // If possible. Assuming that the order number is in the format ORD-{$id}
            $latestOrder = Order::query()
                ->orderBy('id', 'desc')
                ->first(['id', 'number']);

            $id = $i;

            if ($latestOrder) {
                $value = explode('-', $latestOrder->number);

                if ($value[1]) {
                    $id = (int) $value[1] + $i;
                }
            }

            $formRequests[] = [
                'customer_id' => $product->customer->id,
                'number' => "ORD-$id",
                'shipping_method_id' => $shippingMethod->id,
                'order_items' => [[
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'quantity' => $numberOfItems
                ]]
            ];
        }

        $batchRequest = OrderStoreBatchRequest::make($formRequests);

        App::make('order')->storeBatch($batchRequest);
    }

    /**
     * @Given I sync batch orders
     */
    public function iSyncBatchOrders(): void
    {
        App::make(BulkShipComponent::class)->syncBatchOrders(Customer::whereId($this->getWarehouseInScope()->customer_id)->get());
    }

    /**
     * @Then I should have a bulk ship batch with :numberOfOrders orders
     */
    public function iShouldHaveABulkShipBatchWithOrders($numberOfOrders): void
    {
        $bulkShipBatch = BulkShipBatch::whereCustomerId($this->getWarehouseInScope()->customer_id)
            ->first();

        $this->assertEquals($bulkShipBatch->total_orders, $numberOfOrders);
    }

    /**
     * @Then the customer :customerName should have :quantity bulk ship batches
     */
    public function theCustomerShouldHaveBulkShipBatches($customerName, $quantity): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $bulkShipBatches = BulkShipBatch::whereCustomerId($customer->id)
            ->get();

        $this->assertCount($quantity, $bulkShipBatches);
    }

    /**
     * @Then the logged user starts to ship the bulk ship batch
     */
    public function theLoggedUserStartsToShipTheBulkShipBatch(): void
    {
        $bulkShipBatch = BulkShipBatch::whereCustomerId($this->getWarehouseInScope()->customer_id)
            ->first();
        $customer = $this->getCustomerInScope();

        Session::put(UserComponent::SESSION_CUSTOMER_KEY, $customer->id);

        $this->get(route('bulk_shipping.shipping', [$bulkShipBatch->id]));
    }

    /**
     * @Then the logged user ships the bulk ship batch
     */
    public function theLoggedUserShipsTheBulkShipBatch(): void
    {
        $bulkShipBatch = BulkShipBatch::whereCustomerId($this->getWarehouseInScope()->customer_id)
            ->first();
        $customer = $this->getCustomerInScope();
        $location = $this->locationInScope;
        $printer = Printer::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $orders = $bulkShipBatch->orders;
        $firstOrderItem = $orders[0]->orderItems->first();

        $data = [
            'bulk_ship_batch_id' => $bulkShipBatch->id,
            'batch_filter' => [
                'shipping_carrier' => '',
                'shipping_method' => '',
            ],
            'order_shipping_method_mappings' => '[]',
            'customer_id' => $customer->id,
            'mapping_type' => 'regular',
            'total_unpacked_items' => 0,
            'total_unpacked_weight' => 0,
            // use map instead of hardcoded
            'shipping_method_id' => $orders->mapWithKeys(fn ($order) => [$order->id => 'generic'])->toArray(),
            'length' => 1,
            'width' => 1,
            'height' => 1,
            'weight' => 2,
            'order_items' => [
                "0_{$firstOrderItem->id}__{$location->id}_0_1" => [
                    'quantity' => 1,
                    'order_item_id' => $firstOrderItem->id,
                    'location_id' => $location->id,
                    'tote_id' => null,
                ],
            ],
            'batch_shipping_limit' => 3,
            'print_packing_slip' => '',
            'custom-package-length' => '',
            'custom-package-width' => '',
            'custom-package-height' => '',
            'shipping_contact_information' => [
                'name' => 'John Doe',
                'company_name' => 'A',
                'address' => '123 Main St',
                'address2' => 'Apt. 12',
                'zip' => '12345',
                'city' => 'New York',
                'state' => 'New York',
                'country_id' => 1,
                'email' => 'john@doe.com',
                'phone' => '',
                'company_number' => 2,
            ],
            'printer_id' => $printer->id,
            'serial_number' => '',
        ];
        $data['packing_state'] = json_encode([[
            'items' => [
                [
                    'orderItem' => (string) $firstOrderItem->id,
                    'location' => "$location->id",
                    'tote' => 0,
                    'serialNumber' => '',
                    'packedParentKey' => '',
                ]
            ],
            'weight' => 2,
            'box' => $customer->shippingBoxes()->first()->id,
            '_length' => '1',
            'width' => '1',
            'height' => '1',
            'custom' => 0,
        ]]);

        // Make the POST request
        $this->post(route('bulk_shipping.ship', [$bulkShipBatch->id]), $data);
    }

    /**
     * @Then the bulk ship PDF should show orders shipped amount as :amount
     */
    public function theBulkShipPDFShouldShowOrdersShippedAmountAs($amount): void
    {
        $bulkShipBatch = BulkShipBatch::whereCustomerId($this->getWarehouseInScope()->customer_id)
            ->first();

        PDF::shouldReceive('loadView')
            ->once()
            ->with('bulk_shipping.pdf', \Mockery::on(function ($data) use ($amount) {
                // Assert the value of orders_shipped
                $this->assertEquals($amount, $data['ordersShipped']);
                return true;
            }))
            ->andReturnSelf();

        try {
            App::make(PackingComponent::class)->getBulkShipPDF($bulkShipBatch);
        } catch (Exception $e) {
            // We don't care about the exception here, since we're just testing that orders_shipped count is correct
        }
    }

    /**
     * @Then it shouldn't be possible to create duplicate order rows in the bulk ship batch
     */
    public function itShouldntBePossibleToCreateDuplicateOrderRowsInTheBulkShipBatch(): void
    {
        $bulkShipBatch = BulkShipBatch::whereCustomerId($this->getWarehouseInScope()->customer_id)
            ->first();

        $duplicates = [];

        foreach ($bulkShipBatch->orders as $order) {
            $bulkShipBatchOrder = $order->pivot->toArray();
            unset($bulkShipBatchOrder['id']);
            $duplicates[] = $bulkShipBatchOrder;
        }

        try {
            BulkShipBatchOrder::insert($duplicates);
        } catch (Exception $e) {
            $this->assertInstanceOf(QueryException::class, $e);
            $this->assertTrue(Str::contains($e->getMessage(), 'Duplicate entry'));
        }
    }

    /**
     * @Then the order :orderNumber should have a :fieldName date of :date
     */
    public function theOrderShouldHaveADateOf(string $orderNumber, string $fieldName, string $date): void
    {
        $order = Order::where('number', $orderNumber)->firstOrFail();
        $this->assertEquals($date, $order->$fieldName->format('Y-m-d H:i:s'), "The expected date $date does not match the actual date {$order->$fieldName->format('Y-m-d H:i:s')}");

    }

    /**
     * @Then the order :orderNumber should have the warehouse :warehouseName of customer :customerName
     */
    public function theOrderShouldHaveTheWarehouseOfCustomer(string $orderNumber, string $warehouseName, string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $order = Order::where('number', $orderNumber)->firstOrFail();
        $warehouse = Warehouse::where('customer_id', $customer->id)
            ->whereHas('contactInformation', fn (Builder $query) => $query->where('name', $warehouseName))
            ->firstOrFail();

        $this->assertEquals($warehouse->id, $order->warehouse->id);
    }

    /**
     * @Given the order :orderNumber has field :fieldName set to :value
     */
    public function theOrderHasAllowPartialEnabled(string $orderNumber, string $fieldName, $value): void
    {
        $order = Order::whereNumber($orderNumber)->firstOrFail();

        $order->$fieldName = $value;

        $order->save();
    }

    /**
     * @Given the queues are turned off
     */
    public function theQueueIsTurnedOff(): void
    {
        Queue::fake();
    }
}
