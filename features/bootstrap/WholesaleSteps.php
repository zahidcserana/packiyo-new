<?php

use App\Components\WholesaleComponent;
use App\Components\WholesaleIntegrationsComponent;
use App\Events\OrderShippedEvent;
use App\Http\Requests\Order\StoreRequest as OrderStoreRequest;
use App\Models\Customer;
use App\Models\EDI\Providers\CrstlASN;
use App\Models\EDI\Providers\CrstlEDIProvider;
use App\Models\EDI\Providers\CrstlPackingLabel;
use App\Models\EDIProvider;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\PackageOrderItem;
use App\Models\Printer;
use App\Models\PrintJob;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\ShippingBox;
use App\Models\ShippingMethod;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Behat steps to test customers.
 */
trait WholesaleSteps
{
    protected ?array $packages = null;

    protected ?Shipment $shipment = null;

    protected ?EDIProvider $ediProvider = null;

    protected ?CrstlASN $crstlASN = null;

    protected ?MockHandler $mockHandler = null;

    protected ?array $requestBody = null;

    /**
     * @Given the customer :customerName has a Crstl account
     */
    public function theCustomerHasACrstlAccount(string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        CrstlEDIProvider::factory()->sandboxed()->create([
            'customer_id' => $customer->id,
            'access_token' => '', // We want to trigger refreshing.
            'refresh_token' => getenv('TEST_CRSTL_SANDBOX_REFRESH_TOKEN')
        ]);
    }

    /**
     * @Given the customer :customerName gets a set of API tokens using their sandbox Crstl credentials
     */
    public function theCustomerGetsASetOfApiTokensUsingTheirSandboxCrstlCredentials(string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $callback = fn() => App::make(WholesaleIntegrationsComponent::class)->buildRequest()
            ->login(getenv('TEST_SANDBOX_CRSTL_USERNAME'), getenv('TEST_SANDBOX_CRSTL_PASSWORD'), true)
            ->send();
        $response = static::record($callback);

        $responseBody = json_decode($response->getBody()->getContents());
        $this->ediProvider = CrstlEDIProvider::factory()
            ->sandboxed()
            ->create([
                'customer_id' => $customer->id,
                'access_token' => $responseBody->access_token,
                'refresh_token' => $responseBody->refresh_token,
                'access_token_expires_at' => $responseBody->access_token_expires_at,
                'is_multi_crstl_org' => $responseBody->is_multi_org,
                'external_role' => $responseBody->role,
                'external_organization_id' => $responseBody->organization_id
            ]);
    }

    /**
     * @Given the customer :customerName gets a set of API tokens using their production Crstl credentials
     */
    public function theCustomerGetsASetOfApiTokensUsingTheirProductionCrstlCredentials(string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $callback = fn() => App::make(WholesaleIntegrationsComponent::class)->buildRequest()
            ->login(getenv('TEST_PROD_CRSTL_USERNAME'), getenv('TEST_PROD_CRSTL_PASSWORD'))
            ->send();
        $response = static::record($callback);

        $responseBody = json_decode($response->getBody()->getContents());
        $this->ediProvider = CrstlEDIProvider::factory()
            ->create([
                'customer_id' => $customer->id,
                'access_token' => $responseBody->access_token,
                'refresh_token' => $responseBody->refresh_token,
                'access_token_expires_at' => $responseBody->access_token_expires_at,
                'is_multi_crstl_org' => $responseBody->is_multi_org,
                'external_role' => $responseBody->role,
                'external_organization_id' => $responseBody->organization_id
            ]);
    }

    /**
     * @Given the customer :customerName gets a set of mocked API tokens using their Sandbox Crstl credentials
     */
    public function theCustomerGetsASetOfMockedApiTokensUsingTheirSandboxCrstlCredentials(string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $this->mockHandler = new MockHandler([
            new GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], '{"access_token":"mocked_access_token","refresh_token":"mocked_refresh_token","access_token_expires_at":"2022-01-01T00:00:00Z","is_multi_org":false,"role":"mocked_role","organization_id":"mocked_organization_id"}'),
        ]);

        $handlerStack = HandlerStack::create($this->mockHandler);
        $client = new Client(['handler' => $handlerStack]);

        App::bind(Client::class, fn() => $client);

        $response = App::make(WholesaleIntegrationsComponent::class)->buildRequest()
            ->login(getenv('TEST_SANDBOX_CRSTL_USERNAME'), getenv('TEST_SANDBOX_CRSTL_PASSWORD'), true)
            ->send();

        $responseBody = json_decode($response->getBody()->getContents());
        $this->ediProvider = CrstlEDIProvider::factory()
            ->sandboxed()
            ->create([
                'customer_id' => $customer->id,
                'access_token' => $responseBody->access_token,
                'refresh_token' => $responseBody->refresh_token,
                'access_token_expires_at' => $responseBody->access_token_expires_at,
                'is_multi_crstl_org' => $responseBody->is_multi_org,
                'external_role' => $responseBody->role,
                'external_organization_id' => $responseBody->organization_id
            ]);
    }

    /**
     * @Given the customer :customerName gets a set of mocked API tokens using their Crstl credentials
     */
    public function theCustomerGetsASetOfMockedApiTokensUsingTheirCrstlCredentials(string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $this->mockHandler = new MockHandler([
            new GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], '{"access_token":"mocked_access_token","refresh_token":"mocked_refresh_token","access_token_expires_at":"2022-01-01T00:00:00Z","is_multi_org":false,"role":"mocked_role","organization_id":"mocked_organization_id"}'),
        ]);

        $handlerStack = HandlerStack::create($this->mockHandler);
        $client = new Client(['handler' => $handlerStack]);

        App::bind(Client::class, fn() => $client);

        $response = App::make(WholesaleIntegrationsComponent::class)->buildRequest()
            ->login(getenv('TEST_SANDBOX_CRSTL_USERNAME'), getenv('TEST_SANDBOX_CRSTL_PASSWORD'))
            ->send();

        $responseBody = json_decode($response->getBody()->getContents());
        $this->ediProvider = CrstlEDIProvider::factory()->create([
            'customer_id' => $customer->id,
            'access_token' => $responseBody->access_token,
            'refresh_token' => $responseBody->refresh_token,
            'access_token_expires_at' => $responseBody->access_token_expires_at,
            'is_multi_crstl_org' => $responseBody->is_multi_org,
            'external_role' => $responseBody->role,
            'external_organization_id' => $responseBody->organization_id,
            'is_sandbox' => false
        ]);
    }

    /**
     * @Given the customer :customerName got the wholesale order :orderNumber with external ID :orderExternalId for these items
     */
    public function theCustomerGotTheWholesaleOrderWithExternalIdForTheseItems(
        string $customerName, string  $orderNumber, string $orderExternalId, TableNode $orderItemsTable
    ): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $orderItems = [];

        foreach ($orderItemsTable->getRows() as $orderItem) {
            [$quantity, $sku, $orderItemExternalId] = $orderItem;
            $product = Product::where(['customer_id' => $customer->id, 'sku' => $sku])->firstOrFail();

            $orderItems[] = [
                'product_id' => $product->id,
                'sku' => $sku,
                'quantity' => $quantity,
                'external_id' => $orderItemExternalId
            ];
        }

        $formRequest = OrderStoreRequest::make([
            'customer_id' => $customer->id,
            'external_id' => $orderExternalId,
            'number' => $orderNumber,
            'is_wholesale' => true,
            'allow_partial' => true,
            'order_items' => $orderItems
        ]);

        $order = App::make('order')->store($formRequest, false);

        $order->priority = false;
        $order->save();

        if (method_exists($this, 'setCustomerInScope')) {
            $this->setCustomerInScope($customer);
        }
    }

    /**
     * @Given the order number :orderNumber was packed as follows
     */
    public function  theOrderNumberWasPackedAsFollows(string $orderNumber, TableNode $packagesTable): void
    {
        $customer = $this->getCustomerInScope();
        $order = Order::where(['customer_id' => $customer->id, 'number' => $orderNumber])->firstOrFail();
        $packages = [];

        foreach ($packagesTable->getRows() as $package) {
            [$number, $boxName, $quantity, $sku] = $package;
            $box = ShippingBox::where(['customer_id' => $customer->id, 'name' => $boxName])->firstOrFail();

            if (empty($packages[$number])) {
                $packages[$number] = Package::factory()->create([
                    'order_id' => $order->id,
                    'shipping_box_id' => $box->id
                ]);
            }

            $product = Product::where(['customer_id' => $customer->id, 'sku' => $sku])->firstOrFail();
            $orderItem = $order->orderItems->first(fn (OrderItem $item) => $item->sku == $product->sku);
            $packages[$number]->packageOrderItems->push(PackageOrderItem::factory()->create([
                'order_item_id' => $orderItem->id,
                'package_id' => $packages[$number]->id,
                'quantity' => $quantity
            ]));

            $weight = $package[4] ?? null; // Optional weight [4] column.
            if ($weight) {
                $packages[$number]->weight = $weight;
            }
        }

        $this->packages = $packages;
    }

    /**
     * @When the packed order :orderNumber was shipped from the :warehouseName warehouse through :carrierName on the :shippedDate
     */
    public function thePackedOrderWasShippedFromTheWarehouseThroughOnThe(
        string $orderNumber, string $warehouseName, string $carrierName, string $shippedDate
    ): void
    {
        $customer = $this->getCustomerInScope();
        $order = Order::where(['customer_id' => $customer->id, 'number' => $orderNumber])->firstOrFail();
        // TODO: Why is the warehouse not needed?
        // $warehouse = Warehouse::where('customer_id', $customer->id)
        //     ->whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
        //         $query->where('name', $warehouseName);
        //     })
        //     ->firstOrFail();
        $shippingMethod = ShippingMethod::whereHas('shippingCarrier', function (Builder $query) use (&$customer, &$carrierName) {
            $query->where(['customer_id' => $customer->id, 'name' => $carrierName]);
        })->first(); // Could be generic.

        $this->shipment = Shipment::factory()
            ->withPackages(array_values($this->packages))
            ->create([
                // 'user_id' => $user->id,  // Identifies shipping 3PL.
                'order_id' => $order->id,
                'shipping_method_id' => $shippingMethod ? $shippingMethod->id : null,
                'is_freight' => false,
                'created_at' => $shippedDate,
                'updated_at' => $shippedDate
            ]);
    }

    /**
     * @When the packed order :orderNumber is shipped from the :warehouseName warehouse through :carrierName on the :shippedDate
     */
    public function thePackedOrderIsShippedFromTheWarehouseThroughOnThe(
        string $orderNumber, string $warehouseName, string $carrierName, string $shippedDate
    ): void
    {
        $customer = $this->getCustomerInScope();
        $order = Order::where(['customer_id' => $customer->id, 'number' => $orderNumber])->firstOrFail();
        // TODO: Why is the warehouse not needed?
        // $warehouse = Warehouse::where('customer_id', $customer->id)
        //     ->whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
        //         $query->where('name', $warehouseName);
        //     })
        //     ->firstOrFail();
        $shippingMethod = ShippingMethod::whereHas('shippingCarrier', function (Builder $query) use (&$customer, &$carrierName) {
            $query->where(['customer_id' => $customer->id, 'name' => $carrierName]);
        })->first(); // Could be generic.

        $this->shipment = Shipment::factory()
            ->withPackages(array_values($this->packages))
            ->create([
                // 'user_id' => $user->id,  // Identifies shipping 3PL.
                'order_id' => $order->id,
                'shipping_method_id' => $shippingMethod ? $shippingMethod->id : null,
                'is_freight' => false,
                'created_at' => $shippedDate,
                'updated_at' => $shippedDate
            ]);

        $callback = fn () => event(new OrderShippedEvent($order, $this->shipment));

        static::record($callback);
    }

    /**
     * @When we mock that the packed order :orderNumber is shipped from the :warehouseName warehouse through :carrierName on the :shippedDate
     */
    public function thePackedOrderIsShippedFromTheWarehouseThroughOnTheMocked(
        string $orderNumber, string $warehouseName, string $carrierName, string $shippedDate
    ): void
    {
        $customer = $this->getCustomerInScope();
        $order = Order::where(['customer_id' => $customer->id, 'number' => $orderNumber])->firstOrFail();
        // TODO: Why is the warehouse not needed?
        // $warehouse = Warehouse::where('customer_id', $customer->id)
        //     ->whereHas('contactInformation', function (Builder $query) use (&$warehouseName) {
        //         $query->where('name', $warehouseName);
        //     })
        //     ->firstOrFail();
        $shippingMethod = ShippingMethod::whereHas('shippingCarrier', function (Builder $query) use (&$customer, &$carrierName) {
            $query->where(['customer_id' => $customer->id, 'name' => $carrierName]);
        })->first(); // Could be generic.

        $this->shipment = Shipment::factory()
            ->withPackages(array_values($this->packages))
            ->create([
                // 'user_id' => $user->id,  // Identifies shipping 3PL.
                'order_id' => $order->id,
                'shipping_method_id' => $shippingMethod ? $shippingMethod->id : null,
                'is_freight' => false,
                'created_at' => $shippedDate,
                'updated_at' => $shippedDate
            ]);

        $this->mockHandler = new MockHandler([
            new GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], '{}'),
        ]);

        $handlerStack = HandlerStack::create($this->mockHandler);
        $client = new Client(['handler' => $handlerStack]);

        App::bind(Client::class, fn() => $client);

        try {
            event(new OrderShippedEvent($order, $this->shipment));
        } catch (Exception $e){
            // It'll fail because we're not mocking a real response from the Crstl API (the body is {}). We're just testing our request.
        }
    }

    /**
     * @Then we store the response's body
     */
    public function weStoreTheResponseSBody(): void
    {
        $this->requestBody = json_decode($this->mockHandler->getLastRequest()->getBody()->getContents(), true);
    }

    /**
     * @Then the request's packages information should be
     */
    public function thePackageWeightShouldBe(TableNode $information): void
    {
        $information = $information->getRows();
        // Remove header
        array_shift($information);

        foreach ($information as $expectedInformation) {
            [$packageNumber, $weight, $uom] = $expectedInformation;
            $packageNumber = (int) Str::remove('#', $packageNumber) - 1;

            $this->assertEquals($weight, $this->requestBody['packages'][$packageNumber]['measurements']['weight']);
            $this->assertEquals($uom, $this->requestBody['packages'][$packageNumber]['measurements']['weight_uom']);
        }

    }

    /**
     * @Then the request's packages count should be :count
     */
    public function theRequestSPackagesCountShouldBe(int $count): void
    {
        $this->assertCount($count, $this->requestBody['packages']);
    }

    /**
     * @Then the request's measurements weight should be :weight
     */
    public function theRequestSMeasurementsInformationShouldBe(float $weight): void
    {
        $this->assertEquals($weight, $this->requestBody['measurements']['weight']);
    }

    /**
     * @Then the request's measurements weight unit should be :weightUnit
     */
    public function theRequestSMeasurementsWeightUnitShouldBe(string $weightUnit): void
    {
        $this->assertEquals($weightUnit, $this->requestBody['measurements']['weight_uom']);
    }

    /**
     * @When Crstl production base url is :baseUrl
     */
    public function crstlProductionBaseUrlIs(string $baseUrl): void
    {
        config(['crstl_edi.api_base_url' => $baseUrl]);
    }

    /**
     * @When Crstl sandbox base url is :baseUrl
     */
    public function crstlSandboxBaseUrlIs(string $baseUrl): void
    {
        config(['crstl_edi.sandbox_api_base_url' => $baseUrl]);
    }

    /**
     * @Then the latest Crstl API call should've been made to :baseUrl
     */
    public function theLatestCrstlApiCallShouldveBeen(string $baseUrl): void
    {
        $latestRequest = $this->mockHandler->getLastRequest();

        $completeUrl = $latestRequest->getUri()->getScheme() . '://' . $latestRequest->getUri()->getHost() . $latestRequest->getUri()->getPath();

        $this->assertTrue(str_contains($completeUrl, $baseUrl));
    }

    /**
     * @When the packer submits the order :orderNumber ASN information
     */
    public function thePackerSubmitsTheAsnInformation(string $orderNumber)
    {
        $customer = $this->getCustomerInScope();
        $order = Order::where(['customer_id' => $customer->id, 'number' => $orderNumber])->firstOrFail();

        $callback = fn() => App::make(WholesaleComponent::class)->createPackingLabels($this->ediProvider, $order, 0, $this->shipment);

        $this->crstlASN = static::record($callback);
    }

    /**
     * @When the packer submits the order :orderNumber ASN information to a mocked endpoint
     */
    public function thePackerSubmitsTheAsnInformationToAMockedEndpoint(string $orderNumber): void
    {
        $customer = $this->getCustomerInScope();
        $order = Order::where(['customer_id' => $customer->id, 'number' => $orderNumber])->firstOrFail();

        // Create a mock and queue two responses.
        $this->mockHandler = new MockHandler([
            new GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], '{"status":200,"code":"success","data":{"shipment_id":"660f038f1e21de71b26ef4c0","request_labels_after_ms":200,"asn_status":"invalid","shipping_labels_status":"generating"}}'),
        ]);

        $handlerStack = HandlerStack::create($this->mockHandler);
        $client = new Client(['handler' => $handlerStack]);

        App::bind(Client::class, fn() => $client);

        App::make(WholesaleComponent::class)->createPackingLabels($this->ediProvider, $order, 0, $this->shipment);
    }

    /**
     * @When the packer prints the GS1-128 labels
     */
    public function thePackerPrintsThePackingLabels()
    {
        $wholesaleComponent = App::make(WholesaleComponent::class);
        $callback = fn() => $wholesaleComponent->getPackingLabels($this->ediProvider, $this->crstlASN);
        $this->crstlASN = static::record($callback);

        foreach ($this->crstlASN->packingLabels as $packingLabel) {
            // Download the file using Laravel Http client
            $response = Http::get($packingLabel->signed_url);

            // TODO: What are we doing with these files?
            file_put_contents(storage_path('app/thePackerPrintsTheGsLabels_gs1-128.pdf'), $response->getBody()->getContents());

            $packingLabel->signed_url_expires_at = now()->addHours(2);
            $packingLabel->save();
        }

        $printer = Printer::factory()->create([
            'customer_id' => $this->getCustomerInScope()->id,
            'user_id' => $this->getUserInScope()->id
        ]);
        $wholesaleComponent->printPackingLabels($this->crstlASN, $printer);
    }

    /**
     * @Then the printing queue should have :quantity item
     * @Then the printing queue should have :quantity items
     */
    public function thePrintingQueueShouldHaveItem(string $quantity): void
    {
        $this->assertCount($quantity, PrintJob::get()->toArray());
    }

    /**
     * @Then the printing queue should have :count items for the printer :printerName
     * @Then the printing queue should have :count item for the printer :printerName
     */
    public function thePrintingQueueShouldHaveItemsForThePrinterName(string $count, string $printerName): void
    {
        $printer = Printer::where('name', $printerName)->firstOrFail();
        $this->assertCount($count, PrintJob::where('printer_id', $printer->id)->get()->toArray());
    }

    /**
     * @Then the order :orderNumber should have :quantity GS1-128 label queued for printing
     * @Then the order :orderNumber should have :quantity GS1-128 labels queued for printing
     */
    public function theOrderShouldHavePackingLabelsQueuedForPrinting(string $orderNumber, string $quantity): void
    {
        $packingLabels = [];

        foreach (PrintJob::get() as $printJob) {
            if ($printJob->object instanceof CrstlPackingLabel && $printJob->object->asn->order->number == $orderNumber) {
                $packingLabels []= $printJob;
            }
        }

        $this->assertCount($quantity, $packingLabels);
    }
}
