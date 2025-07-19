<?php

use App\Components\Shipping\Providers\EasypostShippingProvider;
use App\Http\Requests\Packing\PackageItemRequest;
use App\Models\{CustomerSetting,
    PrintJob,
    Shipment,
    ShipmentItem,
    Order,
    ShipmentLabel,
    ShippingMethod,};
use Behat\Gherkin\Node\TableNode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\PdfReader\PageBoundaries;
use setasign\Fpdi\Tcpdf\Fpdi;
use App\Components\LabelaryZpl;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Printer;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use App\Components\ZplConverterComponent;

trait ShipmentSteps
{
    private array|null $responseData = [];
    private string|null $responseContent = null;
    private Shipment|null $shipmentInScope = null;

    /**
     * @Then I expect the order shipment to be successful, with a line containing SKU :sku and a shipped quantity of :shippedQuantity
     */
    public function iExpectTheOrderShipmentToBeSuccessfulWithALineContainingSkuAndAShippedQuantityOf(string $sku, $shippedQuantity)
    {
        $order = $this->order;

        $shipment = Shipment::with(['shipmentItems', 'shipmentItems.orderItem'])->where(['order_id' => $order->id])->first();
        $shipmentItem = $shipment->shipmentItems->first(fn (ShipmentItem $shipmentItem) => $shipmentItem->orderItem->sku == $sku);

        $this->assertEquals($shipment->processing_status, Shipment::PROCESSING_STATUS_SUCCESS);
        $this->assertTrue($shipment->cost > 0);
        $this->assertEquals($shipmentItem->orderItem->sku, $sku);
        $this->assertEquals($shipmentItem->orderItem->quantity_shipped, $shippedQuantity);
    }

    /**
     * @Given the order :orderNumber shipment request body for EasypostShippingProvider is generated using shipping method :shippingMethodName
     */
    public function theOrderIsShippedUsingEasypostShippingProviderWithShippingMethod(string $orderNumber, string $shippingMethodName): void
    {
        $customer = $this->getCustomerInScope();
        $order = $customer->orders()->where('number', $orderNumber)->firstOrFail();
        $shippingMethod = ShippingMethod::whereName($shippingMethodName)->firstOrFail();

        $this->packingRequestData['shipping_method_id'] = $shippingMethod?->id;
        $this->packingRequestData['customer_id'] = $customer->id;

        // TODO Why packingRequestData['packing_state'] is an array? IN the actual code (app/Components/Shipping/Providers/EasypostShippingProvider.php:1815)
        // it loops this array to createShipments.
        $packageItemRequest = PackageItemRequest::make($this->packingRequestData['packing_state'][0]);
        $this->shipmentRequestBody = App::make(EasypostShippingProvider::class)->createShipmentRequestBody($order, $packageItemRequest, $shippingMethod, 'pdf');
    }

    /**
     * @Then the Easypost shipment request body should have
     */
    public function theEasypostShipmentRequestBodyShouldHave(TableNode $expectedStructure): void
    {
        foreach ($expectedStructure->getHash() as $row) {
            $orderLineItemInRequest = collect($this->shipmentRequestBody['shipment']['customs_info']['customs_items'])
                ->where('code', $row['code']);

            $this->assertQuantityAndPrice($orderLineItemInRequest, $row, 'value');
        }
    }

    /**
     * @Then the Tribird shipment request body should have
     */
    public function theTribirdShipmentRequestBodyShouldHave(TableNode $expectedStructure): void
    {
        foreach ($expectedStructure->getHash() as $row) {
            $orderLineItemInRequest = collect($this->shipmentRequestBody[0]['package_items'])
                ->where('sku', $row['sku']);

            $this->assertQuantityAndPrice($orderLineItemInRequest, $row, 'price');
        }
    }

    /**
     * @Then the Webshipper shipment request body should have
     */
    public function theWebshipperShipmentRequestBodyShouldHave(TableNode $expectedStructure): void
    {
        foreach ($expectedStructure->getHash() as $row) {
            $orderLineItemInRequest = collect($this->shipmentRequestBody['data']['attributes']['packages'][0]['customs_lines'])
                ->where('sku', $row['sku']);

            $this->assertQuantityAndPrice($orderLineItemInRequest, $row, 'unit_price');
        }
    }

    /**
     * @Then the ExternalCarrier shipment request body should have
     */
    public function theExternalCarrierShipmentRequestBodyShouldHave(TableNode $expectedStructure): void
    {
        foreach ($expectedStructure->getHash() as $row) {
            $orderLineItemInRequest = collect($this->shipmentRequestBody['packages'][0]['order_lines'])
                ->where('sku', $row['sku']);

            $this->assertQuantityAndPrice($orderLineItemInRequest, $row, 'unit_price');
        }
    }

    /**
     * @Given the order :orderNumber shipment request body for TribirdShippingProvider is generated using shipping method :shippingMethodName
     */
    public function theOrderIsShippedUsingTribirdShippingProviderWithShippingMethod(string $orderNumber, string $shippingMethodName): void
    {
        $customer = $this->getCustomerInScope();
        $order = $customer->orders()->where('number', $orderNumber)->firstOrFail();
        $shippingMethod = ShippingMethod::whereName($shippingMethodName)->firstOrFail();

        $this->packingRequestData['shipping_method_id'] = $shippingMethod?->id;
        $this->packingRequestData['customer_id'] = $customer->id;

        $result = [];

        foreach ($this->packingRequestData['packing_state'] as $packing) {
            $result[] = App::make(\App\Components\Shipping\Providers\TribirdShippingProvider::class)->createShippingRateRequestBody($order, $this->packingRequestData, $packing);
        }

        $this->shipmentRequestBody = $result;
    }

    /**
     * @Given the order :orderNumber shipment request body for WebshipperShippingProvider is generated using shipping method :shippingMethodName
     */
    public function theOrderIsShippedUsingWebshipperShippingProviderWithShippingMethod(string $orderNumber, string $shippingMethodName): void
    {
        $customer = $this->getCustomerInScope();
        $order = $customer->orders()->where('number', $orderNumber)->firstOrFail();
        $shippingMethod = ShippingMethod::whereName($shippingMethodName)->firstOrFail();

        $this->packingRequestData['shipping_method_id'] = $shippingMethod?->id;
        $this->packingRequestData['customer_id'] = $customer->id;

        $this->packingRequestData['packing_state'] = json_encode($this->packingRequestData['packing_state']);

        $this->shipmentRequestBody = App::make(\App\Components\Shipping\Providers\WebshipperShippingProvider::class)->createShipmentRequestBody($order, $this->packingRequestData, $shippingMethod);
    }

    /**
     * @Given the order :orderNumber shipment request body for ExternalCarrierShippingProvider is generated using shipping method :shippingMethodName
     */
    public function theOrderIsShippedUsingExternalCarrierShippingProviderWithShippingMethod(string $orderNumber, string $shippingMethodName): void
    {
        $customer = $this->getCustomerInScope();
        $order = $customer->orders()->where('number', $orderNumber)->firstOrFail();
        $shippingMethod = ShippingMethod::whereName($shippingMethodName)->firstOrFail();

        $this->packingRequestData['shipping_method_id'] = $shippingMethod?->id;
        $this->packingRequestData['customer_id'] = $customer->id;

        $this->packingRequestData['packing_state'] = json_encode($this->packingRequestData['packing_state']);

        $this->shipmentRequestBody = App::make(\App\Components\Shipping\Providers\ExternalCarrierShippingProvider::class)->createShipmentRequestBody($order, $this->packingRequestData, $shippingMethod);
    }

    /**
     * @Then I expect the order shipment labels to include tracking links
     */
    public function iExpectTheOrderShipmentLabelsToIncludeTrackingLinks()
    {
        $order = $this->order;

        $shipment = Shipment::with(['shipmentLabels', 'shipmentTrackings'])->where(['order_id' => $order->id])->first();

        $this->assertTrue($shipment->shipmentLabels->count() > 0);
        $this->assertTrue($shipment->shipmentTrackings->count() > 0);
        $this->assertNotNull($shipment->shipmentLabels->first()->url);
        $this->assertNotNull($shipment->shipmentTrackings->first()->tracking_number);
        $this->assertNotNull($shipment->shipmentTrackings->first()->tracking_url);

    }

    /**
     * @Then I void the order shipment labels
     */
    public function iVoidTheOrderShipmentLabels(PyStringNode $note): void
    {
        $order = $this->order;

        $shipment = Shipment::with(['shipmentLabels', 'shipmentTrackings'])->where(['order_id' => $order->id])->first();

        $response = app('shipping')->void($shipment);

        $this->assertTrue($response['success']);
        $this->assertEquals($response['message'], $note);
    }

    /**
     * @Then I expect the order shipment is voided
     */
    public function iExpectTheOrderShipmentIsVoided(): void
    {
        $order = $this->order;

        $shipment = Shipment::with(['shipmentLabels', 'shipmentTrackings'])->where(['order_id' => $order->id])->first();

        $this->assertNotNull($shipment->voided_at);
    }

    /**
     * @Given I want to convert simple ZPL :zplCode to PDF with options
     */
    public function iWantToConvertSimpleZplToPdfWithOptions(string $zplCode, TableNode $tableNode = null): void
    {
        $options = [];
        if (!is_null($tableNode)) {
            $fields = $tableNode->getRow(0);
            $values = $tableNode->getRow(1);
            $options = static::prepareAttributes($fields, $values);
        }

        $callback = fn() => App::make(ZplConverterComponent::class)->convert($zplCode, $options);

        $response = static::record($callback);

        $this->responseContent = $response;
    }

    /**
     * @Given I want to convert simple ZPL remote url :zplUrl to PDF with options
     */
    public function iWantToConvertRemoteSimpleZplUrlToPdfWithOptions(string $zplUrl, TableNode $tableNode): void
    {
        $this->iWantToConvertSimpleZplToPdfWithOptions($zplUrl, $tableNode);
    }

    /**
     * @Then the method returns response that contains label content
     */
    public function theMethodReturnResponseThatContainsLabelContent(): void
    {
        $this->assertNotEmpty($this->responseContent);
    }

    /**
     * @Then the method returns response that contains type and label
     */
    public function theMethodReturnsResponseThatContainsTypeAndLabel(): void
    {
        $this->assertNotEmpty($this->responseData);
        $this->assertArrayHasKey('type', $this->responseData);
        $this->assertArrayHasKey('label', $this->responseData);
        $this->assertEquals($this->responseData['type'], 'application/pdf');
    }

    /**
     * @Given I want to have shipment with shipping method :methodName on order :orderNumber and I have shipment label with ZPL remote url :zplUrl and when storing I want to convert that file to PDF
     */
    public function iWantToHaveShipmentWithShippingMethodOnOrderWithShipmentLabel(string $orderNumber,
                                                                                  string $methodName,
                                                                                  string $zplUrl): void
    {

        $user = $this->getUserInScope();
        $order = Order::whereNumber($orderNumber)->firstOrFail();
        $shippingMethod = ShippingMethod::whereName($methodName)->firstOrFail();

        $labelFormat = customer_settings($order->customer_id, CustomerSetting::CUSTOMER_SETTING_USE_ZPL_LABELS) ? 'zpl' : 'pdf';
        $this->assertTrue($labelFormat === 'zpl', 'Customer not using zpl labels as default format');

        $response = [
            'options' => [
                'label_format' => $labelFormat,
            ],
            'postage_label' => [
                'label_url' => $zplUrl
            ]
        ];

        $shipment = Shipment::factory()
            ->create([
                'order_id' => $order->id,
                'shipping_method_id' => $shippingMethod->id,
                'processing_status' => Shipment::PROCESSING_STATUS_SUCCESS,
                'user_id' => $user->id
            ]);

        ShipmentLabel::factory()
            ->for($shipment)
            ->create([
                'url' => $zplUrl,
                'type' => ShipmentLabel::TYPE_SHIPPING,
                'document_type' => $labelFormat === 'zpl' ? 'pdf' : $labelFormat,
                'content' => base64_encode($this->getLabelContent($shipment, $response))
            ]);

        $shipment->refresh();

        $this->setShipmentInScope($shipment);
    }

    /**
     * @Then the shipment label with url :zplUrl should have PDF document type and content
     */
    public function theShipmentLabelWithUrlShouldHavePdfDocumentAndContent(string $zplUrl): void
    {
        $shipmentLabel = $this->getShipmentInScope()?->shipmentLabels->first(fn (ShipmentLabel $shipmentLabel) => $shipmentLabel->url == $zplUrl);

        $this->assertEquals($shipmentLabel->document_type, 'pdf');
        $this->assertTrue(!is_null($shipmentLabel->content));
    }

    /**
     * @param Model $object
     * @param $carrierResponse
     * @return string
     */
    private function getLabelContent(Model $object, $carrierResponse): string
    {
        try {
            $carrierAccountType = Arr::get($object->shippingMethod->shippingCarrier->settings, 'carrier_account_type');
            $documentType = strtolower(Arr::get($carrierResponse, 'options.label_format'));
            $labelUrl = Arr::get($carrierResponse, 'postage_label.label_url');

            $labelWidth = paper_width($object->order->customer_id, 'label');
            $labelHeight = paper_height($object->order->customer_id, 'label');

            $zplCode = null;
            if ($documentType === 'zpl') {
                $zplContent = file_get_contents($labelUrl);
                $zplCode = app('zplConverter')->convert($zplContent) ?? null;
                $labelWidth = paper_size_in_pt($object->order->customer_id, LabelaryZPL\Endpoint\Printers::DEFAULT_WIDTH, 'in');
                $labelHeight = paper_size_in_pt($object->order->customer_id, LabelaryZPL\Endpoint\Printers::DEFAULT_HEIGHT, 'in');
            }

            $tmpLabelPath = tempnam(sys_get_temp_dir(), 'label');

            file_put_contents($tmpLabelPath, !is_null($zplCode) ? $zplCode : file_get_contents($labelUrl));

            $fpdi = new Fpdi('P', 'pt', [$labelWidth, $labelHeight]);
            $fpdi->setPrintHeader(false);
            $fpdi->setPrintFooter(false);

            $pageCount = $fpdi->setSourceFile($tmpLabelPath);

            for ($i = 1; $i <= $pageCount; $i++) {
                $fpdi->AddPage();
                $tplId = $fpdi->importPage($i, PageBoundaries::ART_BOX);
                $size = $fpdi->getTemplateSize($tplId);
                $adjustOrientation = true;

                if ($adjustOrientation) {
                    $orientation = Arr::get($size, 'orientation');

                    if ($orientation) {
                        $fpdi->setPageOrientation($orientation);
                    }
                }

                $fpdi->useTemplate($tplId, $size);

            }

            return $fpdi->Output('label.pdf', 'S');
        } catch (Exception $exception) {
            Log::error('[Easypost] getLabelContent', [$exception->getMessage()]);
            return '';
        }
    }

    /**
     * @When I create a printJob for the printer :printName for the Shipping in scope
     */
    public function iCreateAPrintJobForThePrinterShipmentInScope(string $printerName): void
    {
        $shipment = $this->getShipmentInScope();

        $printer = Printer::factory()->create([
            'name' => $printerName
        ]);

        PrintJob::factory()->create([
            'object_id' => $shipment->id,
            'user_id' => $shipment->user_id,
            'printer_id' => $printer->id
        ]);
    }

    /**
     * @param  Collection  $orderLineItemInRequest
     * @param  mixed  $row
     * @return void
     */
    public function assertQuantityAndPrice(Collection $orderLineItemInRequest, mixed $row, string $unit_price_name): void
    {
        $this->assertNotNull($orderLineItemInRequest);
        $this->assertCount(1, $orderLineItemInRequest);
        $this->assertEquals($orderLineItemInRequest->first()['quantity'], $row['quantity']);
        $this->assertEquals($orderLineItemInRequest->first()[$unit_price_name], $row[$unit_price_name]);
    }

    /**
     * @When I create a new Shipment for the customer :customerName
     */
    public function createANewShipmentForTheCustomer(string $customerName): void
    {
        $customer = \App\Models\Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'number' => '99999999'
        ]);

        $shipment = Shipment::factory()->create(['order_id' => $order->id]);
        $shipment->refresh();

        $this->setShipmentInScope($shipment);
    }

    /**
     * @When I delete the Shipment in scope
     */
    public function iDeleteTheShipmentInScope(): void
    {
        $shipment = $this->shipmentInScope;
        $shipment->delete();
    }

    public function setShipmentInScope(Shipment $shipment): void
    {
        $this->shipmentInScope = $shipment;
    }

    public function getShipmentInScope(): Shipment
    {
        if (is_null($this->shipmentInScope )) {
            throw new PendingException("TODO: No shipment is in scope yet.");
        }

        return $this->shipmentInScope;
    }
}
