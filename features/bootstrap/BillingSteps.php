<?php

use App\Http\Requests\Invoice\BatchStoreRequest;
use App\Models\{BulkInvoiceBatch,
    CacheDocuments\PurchaseOrderCacheDocument,
    CacheDocuments\PurchaseOrderChargeCacheDocument,
    Customer,
    CacheDocuments\StorageByLocationChargeCacheDocument,
    CacheDocuments\PackagingRateShipmentCacheDocument,
    CacheDocuments\PickingBillingRateShipmentCacheDocument,
    CacheDocuments\ShipmentCacheDocument,
    CacheDocuments\ShippingLabelRateShipmentCacheDocument,
    Invoice,
    Order,
    RateCard,
    Shipment};
use App\Enums\InvoiceStatus;
use App\Components\MailComponent;
use App\Jobs\Billing\RecalculateInvoiceJob;
use App\Mail\InvoiceCalculationDone;
use App\Http\Requests\Invoice\StoreRequest as InvoiceStoreRequest;
use App\Components\BillingRates\StorageByLocationRate\DocDbLocationsUsageBillingCalculator;
use App\Components\InvoiceComponent;
use App\Components\InvoiceExportComponent;
use App\Features\Wallet;
use App\Jobs\Billing\CalculateInvoiceJob;
use Behat\Behat\Tester\Exception\PendingException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;

/**
 * Behat steps to test 3PL billing rates and invoices.
 */
trait BillingSteps
{
    protected ?string $errorMessage = null;
    protected array|null $exportedInvoiceData = null;
    public Collection|null $shippingChargeDocument = null;
    public Collection|null $shipmentDocument = null;
    public Collection|null $packagingRateCacheDocument = null;
    private MockInterface|null $docDbLocationsUsageBillingCalculatorSpy = null;

    protected $mailComponent = null;

    /**
     * @Given the 3PL :threePLName has a rate card :cardName assigned to its client :customerName
     */
    public function the3PLHasARateCardAssignedToItsClient(string $threePLName, string $cardName, string $customerName): void
    {
        $threePL = Customer::whereHas('contactInformation', function (Builder $query) use (&$threePLName) {
            $query->where('name', $threePLName);
        })->firstOrFail();
        $customers = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->get();
        RateCard::factory()
            ->hasAttached($customers, ['priority' => RateCard::PRIMARY_RATE_CARD_PRIORITY], 'customers')
            ->create(['3pl_id' => $threePL->id, 'name' => $cardName]);
    }

    /**
     * @When rate card :rateCardName is unassigned to client :customerName
     */
    public function rateCardIsUnsassignedToClient($rateCardName, $customerName)
    {
        $rateCard = RateCard::where('name', $rateCardName)->firstOrFail();

        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->first();

        $customer->rateCards()->detach($rateCard->id);
        $customer->save();
    }

    /**
     * @When I calculate invoice for 3pl :threePlName clients for the period :startDate to :endDate I get an error with message :errorMessage
     */
    public function iCalculateInvoiceForPlClientsForThePeriodToIGetAnErrorWithMessage($threePlName, $startDate, $endDate, $errorMessage)
    {
        Carbon::setTestNow();//reset time to present
        /** @var Customer $threePlCustomer */
        $threePlCustomer = Customer::whereHas('contactInformation', function (Builder $query) use (&$threePlName) {
            $query->where('name', $threePlName);
        })->firstOrFail();

        $request = \Mockery::mock(BatchStoreRequest::class);
        $request->shouldReceive('all')->once()->andReturn([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'customer_ids' => $threePlCustomer->children()->pluck('id')->toArray()
        ]);
        $invoiceExportComponent = \Mockery::mock(InvoiceExportComponent::class);
        if (empty($this->mailComponent)) {
            $this->disableMailing();
        }

        $mailComponent = $this->mailComponent;
        $invoiceComponent = new InvoiceComponent($invoiceExportComponent, $mailComponent);
        try {
            $invoiceComponent->batchStore($request, $threePlCustomer, auth()->user()); // Dispatches CalculateInvoiceJob.
        }catch (Throwable $exception){
            $this->assertEquals($errorMessage, $exception->getMessage());
        }
    }


    /**
     * @When I calculate an invoice for customer :customerName for the period :startDate to :endDate
     */
    public function iCalculateAnInvoiceForCustomerForThePeriodTo(string $customerName, string $startDate, string $endDate): void
    {
        Carbon::setTestNow();//reset time to present
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        if ($customer->parent->hasFeature(Wallet::class)) {
            $this->setCustomerInScope($customer);
            $this->docDbLocationsUsageBillingCalculatorSpy = $this->spy(DocDbLocationsUsageBillingCalculator::class);
            $this->docDbLocationsUsageBillingCalculatorSpy->shouldReceive('calculate')->passthru();
        } else {
            $this->docDbLocationsUsageBillingCalculatorSpy = null;
        }

        $request = \Mockery::mock(InvoiceStoreRequest::class);
        $request->shouldReceive('all')->once()->andReturn([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'customer_id' => $customer->id
        ]);
        $invoiceExportComponent = \Mockery::mock(InvoiceExportComponent::class);
        if (empty($this->mailComponent)) {
            $this->disableMailing();
        }
        $mailComponent = $this->mailComponent;
        $invoiceComponent = new InvoiceComponent($invoiceExportComponent, $mailComponent);
        $this->expectsJobs(CalculateInvoiceJob::class); // Puts job in dispatchedJobs but it is not handled.
        $response = $invoiceComponent->store($request, auth()->user()); // Dispatches CalculateInvoiceJob.
        $response = TestResponse::fromBaseResponse($response); // A workaround to test HTTP requests.
        $response->assertRedirect(route('billings.customer_invoices', ['customer' => $customer->id]));
        $this->assertCount(1, $this->dispatchedJobs);
    }

    /**
     * @Then it should have used :calculator data for the calculation
     */
    public function itShouldHaveUsedCalculatorDataForTheCalculation(string $calculator): void
    {
        $calculators = ['DocDb', 'MySql'];

        if (!in_array($calculator, $calculators)) {
            throw new PendingException("Unknown calculator: $calculator");
        }

        if ($calculator === 'DocDb') {
            $this->docDbLocationsUsageBillingCalculatorSpy->shouldHaveReceived('calculate');
        }
    }

    /**
     * @Then one invoice item has the description :description
     */
    public function oneInvoiceItemHasTheDescription(string $description): void
    {
        $calculateInvoiceJob = $this->dispatchedJobs[0];
        $invoiceLineItems = $calculateInvoiceJob->invoice->invoiceLineItems()->where('description', $description)->get();
        $invoiceLineItems = $invoiceLineItems->toArray();
        $this->assertNotEmpty($invoiceLineItems, 'There is no fee with the description "' . $description . '".');
        $this->assertCount(1, $invoiceLineItems, 'There is more than one fee with the description "' . $description . '".');
    }

    /**
     * @Then the invoice is exported to a file named :filename
     */
    public function theInvoiceIsExportedToaFileNamed(string $filename): void
    {
        $exportInvoiceJob = $this->dispatchedJobs[0];
        $this->assertStringEndsWith($filename, $exportInvoiceJob->invoice->csv_url);
        $exportedFilePath = storage_path('app/') . $exportInvoiceJob->invoice->csv_url;
        $this->assertFileExists($exportedFilePath);
        $invoiceId = $exportInvoiceJob->invoice->id;

        $fakeInvoiceExport = <<<FAKE_INVOICE_EXPORT
"Type (charge/invoice item)","Name (charge/invoice item)","Description (charge/invoice item)","Quantity (charge/invoice item)","Unit Price (charge/invoice item)","Total Price (charge/invoice item)","Item Period end (charge/invoice item)","SKU (Product)","Name (Product)","Weight (Product)","Height (Product)","Length (Product)","Width (Product)",Shipment,Customer,"Invoice id","Invoice Period Start","Invoice Period End","Client Name","Client Order Reference","Delivery Name","Delivery Address",Country,Carrier,Service,"Total weight (g)","Tracking Number","Number of units in shipment","Date Dispatched","Order No","Tracking No"
shipments_by_picking_rate_v2,"Test Picking Rate","Order: 123456789, TN: TN-123456789 | test",1.00,1.00,1.00,"2023-07-28 00:00:00",,,,,,,,"Test 3PL Client",$invoiceId,2023-06-29,2023-07-28,"Test 3PL Client",,,,,,,,,,,123456789,TN-123456789

FAKE_INVOICE_EXPORT;

        Storage::disk('local')->put('expected_invoice_export.csv', $fakeInvoiceExport);
        $expectedFilePath = storage_path('app/expected_invoice_export.csv');
        $this->assertFileEquals($expectedFilePath, $exportedFilePath);
        $this->exportedInvoiceData = self::csvToArray($exportedFilePath);

        Storage::disk()->delete($expectedFilePath); // TODO: Move to tear down.
    }

    /**
     * @Then the first exported line contains the :columnName as :columnValue
     */
    public function theFirstExportedLineContainsTheAs(string $columnName, string $columnValue): void
    {
        $this->assertCount(1, $this->exportedInvoiceData);
        $this->assertArrayHasKey(0, $this->exportedInvoiceData);
        $this->assertArrayHasKey($columnName, $this->exportedInvoiceData[0]);
        $this->assertEquals($this->exportedInvoiceData[0][$columnName], $columnValue);
    }

    /**
     * @Then the first exported line contains the :arg1 as the invoice's db ID
     */
    public function theFirstExportedLineContainsTheAsTheInvoicesDbId(string $columnName): void
    {
        $this->assertCount(1, $this->exportedInvoiceData);
        $this->assertArrayHasKey(0, $this->exportedInvoiceData);
        $this->assertArrayHasKey($columnName, $this->exportedInvoiceData[0]);

        $calculateInvoiceJob = $this->dispatchedJobs[0];
        $this->assertEquals($this->exportedInvoiceData[0][$columnName], $calculateInvoiceJob->invoice->id);
    }

    static protected function csvToArray(string $csvFilePath): array
    {
        $fileContent = file_get_contents($csvFilePath);
        $lines = explode(PHP_EOL, $fileContent);
        $headers = str_getcsv(array_shift($lines));
        $dataArray = [];

        foreach (array_slice($lines, 0, -1) as $line) {
            $rowData = str_getcsv($line);
            $dataArray[] = array_combine($headers, $rowData);
        }

        return $dataArray;
    }

    /**
     * @Then :count invoice item for :amount has a quantity of :quantity and the description :description
     */
    public function oneInvoiceItemForHasAQuantityOfAndTheDescription(string $count, ?string $amount, string $quantity, string $description): void
    {
        if (empty($amount)) {
            return;
        }

        $calculateInvoiceJob = $this->dispatchedJobs[0];
        $invoiceLineItems = $calculateInvoiceJob->invoice->invoiceLineItems()->where([
            'description' => $description,
            'total_charge' => $amount,
            'quantity' => $quantity
        ])->get();
        $this->assertTrue(!$invoiceLineItems->isEmpty(), 'There are no fees matching the criteria.');
        $this->assertEquals($count, $invoiceLineItems->count(), 'There are not exactly ' . $count . ' fees with matching the criteria.');
    }

    /**
     * @Then the client :customerName should have :quantity storage by location charges that bills from :fromDate to :toDate
     */
    public function theClientShouldHaveStorageByLocationChargeThatBillsFromTo(string $customerName, int $quantity, string $fromDate, string $toDate): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail(['id']);
        $charges = StorageByLocationChargeCacheDocument::query()
            ->where('customer_id', $customer->id)
            ->period(Carbon::createFromFormat('Y-m-d H:i:s', $fromDate), Carbon::createFromFormat('Y-m-d H:i:s', $toDate)->endOfDay())
            ->count();

        $this->assertEquals($quantity, $charges);
    }

    /**
     * @Then invoice item correspond to carrier that was deactivated
     */
    public function invoiceItemCorrespondToCarrierThatWasDeactivated()
    {
        $calculateInvoiceJob = $this->dispatchedJobs[0];

        $invoiceItemAmount = $calculateInvoiceJob->invoice->invoiceLineItems->count();
        if ($invoiceItemAmount == 1) {
            $shipmentId = $calculateInvoiceJob->invoice->invoiceLineItems->first()->shipment_id;
            $shipmentMethod = Shipment::find($shipmentId)->shippingMethod()->first();
            $this->assertTrue($shipmentMethod->trashed());
        } else {
            throw new Exception('More than one invoice item');
        }
    }

    /**
     * @Then :documentCount shipping label charges cache for order number :orderNumber are generated
     */
    public function shippingLabelChargesDocumentForOrderNumberAreGenerated($documentCount, $orderNumber)
    {
        $order = Order::where(['number' => $orderNumber])->first();
        $shippingChargesDocument = ShippingLabelRateShipmentCacheDocument::whereRaw(['order_id' => $order->id])->get();
        $this->shippingChargeDocument = $shippingChargesDocument;
        $shippingChargesDocumentCount = $shippingChargesDocument->count();
        $this->assertEquals($documentCount, $shippingChargesDocumentCount);
    }

    /**
     * @Then :documentCount package charges cache for order number :orderNumber are generated
     */
    public function packageChargesCacheForOrderNumberAreGenerated($documentCount, $orderNumber)
    {
        $order = Order::where(['number' => $orderNumber])->first();
        $packagingRateCacheDocument = PackagingRateShipmentCacheDocument::whereRaw(['order_id' => $order->id])->get();
        $this->packagingRateCacheDocument = $packagingRateCacheDocument;
        $packagingRateCacheDocumentCount = $packagingRateCacheDocument->count();
        $this->assertEquals($documentCount, $packagingRateCacheDocumentCount);
    }

    /**
     * @Then :numberOfCharges charge item for :amount has a quantity of :quantity
     */
    public function chargeItemForHasAQuantityOf($numberOfCharges, $amount, $quantity)
    {
        $this->assertEquals($numberOfCharges, count($this->shippingChargeDocument->first()->charges));
        $charges = collect($this->shippingChargeDocument->first()->charges);
        $chargedItems = $charges->where('total_charge', '=', $amount)
            ->where('quantity', '=', $quantity);
        $this->assertTrue(!$chargedItems->isEmpty(), 'There are no charges matching the criteria.');
    }


    /**
     * @Then :count charged item for :amount has a quantity of :quantity and the description :description
     */
    public function chargedItemForHasAQuantityOfAndTheDescription(string $count, ?string $amount, string $quantity, string $description): void
    {
        if (empty($amount)) {
            return;
        }

        $chargeDocument = $this->chargeDocument;
        $charges = collect($chargeDocument->charges);
        $this->checkShippingDocumentForFees($charges, $description, $amount, $quantity, $count);
    }
    /**
     * @Then a new :count picking charged item for :amount has a quantity of :quantity and the description :description was generated
     */
    public function aNewPickingChargedItemForHasAQuantityOfAndTheDescriptionWasGenerated(string $count, ?string $amount, string $quantity, string $description): void
    {
        if (empty($amount)) {
            return;
        }

        $chargeDocument = PickingBillingRateShipmentCacheDocument::all()->first();
        $charges = collect($chargeDocument->charges);
        $this->checkShippingDocumentForFees($charges, $description, $amount, $quantity, $count);
    }

    /**
     * @Then :count shipping label charged item for :amount has a quantity of :quantity and the description :description
     */
    public function shippingLabelChargedItemForHasAQuantityOfAndTheDescription(string $count, ?string $amount, string $quantity, string $description): void
    {
        if (empty($amount)) {
            return;
        }

        $chargeDocument = $this->shippingChargeDocument->first();
        $charges = collect($chargeDocument->charges);
        $this->checkShippingDocumentForFees($charges, $description, $amount, $quantity, $count);
    }

    /**
     * @Then a new :count shipping label charged item for :amount has a quantity of :quantity and the description :description was generated
     */
    public function aNewShippingLabelChargedItemForHasAQuantityOfAndTheDescriptionWasGenerated(string $count, ?string $amount, string $quantity, string $description): void
    {
        if (empty($amount)) {
            return;
        }

        $shippingCacheDoc = ShippingLabelRateShipmentCacheDocument::all();
        $chargeDocument = $shippingCacheDoc->first();
        $charges = collect($chargeDocument->charges);
        $this->checkShippingDocumentForFees
        ($charges, $description, $amount, $quantity, $count);
    }

    /**
     * @When shipment document for the order number :orderNumber doesnt contain any billing rate
     */
    public function shipmentDocumentForTheOrderNumberDoesntContainAnyBillingRate($orderNumber)
    {
        $shipmentDocument = ShipmentCacheDocument::whereRaw(['order.number'=> $orderNumber])->first();
        $shipmentDocument->calculated_billing_rates = [];
        $shipmentDocument->save();
    }

    /**
     * @When purchase order document for the order number :orderNumber doesnt contain any billing rate
     */
    public function purchaseOrderDocumentForTheOrderNumberDoesntContainAnyBillingRate($orderNumber)
    {
        $document = PurchaseOrderCacheDocument::whereRaw(['purchase_order_number'=> $orderNumber])->first();
        $document->calculated_billing_rates = [];
        $document->save();
    }

    /**
     * @When we lost the shipment caches for the order number :orderNumber
     */
    public function weLostTheShipmentCache(string $orderNumber)
    {
        $shipmentDocument = ShipmentCacheDocument::whereRaw(['order.number'=> $orderNumber])->get();
        $shipmentDocument->each->delete();
    }

    /**
     * @Then :documentCount shipment cache document was deleted
     */
    public function shipmentCacheDocumentWasDeleted($documentCount)
    {
        $shipmentDocument = ShipmentCacheDocument::onlyTrashed()->get();
        $this->assertTrue($shipmentDocument->count() == $documentCount);
    }

    /**
     * @Then :documentCount receiving cache document was deleted
     */
    public function receivingCacheDocumentWasDeleted($documentCount)
    {
        $receivingDocument = PurchaseOrderCacheDocument::onlyTrashed()->get();
        $this->assertTrue($receivingDocument->count() == $documentCount);
    }

    /**
     * @Then :documentCount shipment charge cache document was deleted
     */
    public function shipmentChargeCacheDocumentWasDeleted($documentCount)
    {
        $chargeCount = 0;
        $chargeCount += ShippingLabelRateShipmentCacheDocument::onlyTrashed()->count();
        $chargeCount += PickingBillingRateShipmentCacheDocument::onlyTrashed()->count();
        $chargeCount += PackagingRateShipmentCacheDocument::onlyTrashed()->count();
        $this->assertTrue($chargeCount == $documentCount);
    }

    /**
     * @Then :documentCount receiving charge cache document was deleted
     */
    public function receivingChargeCacheDocumentWasDeleted($documentCount)
    {
        $chargeCount = 0;
        $chargeCount += PurchaseOrderChargeCacheDocument::onlyTrashed()->count();
        $this->assertTrue($chargeCount == $documentCount);
    }

    /**
     * @When we lost the purchase Order caches for the order number :orderNumber
     */
    public function weLostThePurchaseOrder(string $orderNumber)
    {
        $shipmentDocument = PurchaseOrderCacheDocument::where(['purchase_order_number'=> $orderNumber])->get();
        $shipmentDocument->each->delete();
    }

    /**
     * @Then shipment document for order number :orderNumber contains :numberOfShipments shipments
     */
    public function shipmentDocumentForOrderNumberContainsShipments($orderNumber, $numberOfShipments)
    {
        $shipmentDocument = ShipmentCacheDocument::whereRaw(['order.number'=> $orderNumber])->first();
        $this->assertTrue(
            count($shipmentDocument->getShipments()) == $numberOfShipments,
            'Number of shipments not matching expectation'
        );
    }

    /**
     * @Then shipment document for order number :orderNumber contains multiple shipments
     */
    public function multipleShipments($orderNumber)
    {
        $shipmentDocument = ShipmentCacheDocument::whereRaw(['order.number'=> $orderNumber])->first();
        $this->assertTrue(count($shipmentDocument->getShipments()) > 1);
    }

    /**
     * @Then shipment document calculated billing rates is empty
     */
    public function shipmentDocumentContainsCalculatedBillingRatesIsEmpty()
    {
        $doc = $this->shipmentDocument->first();
        $billingRates = collect($doc->calculated_billing_rates);
        $billingRates->each(function($billingRate) {
            $this->assertEmpty($billingRate);
        });
    }

    /**
     * @Then shipment document calculated billing rates has no charges
     */
    public function shipmentDocumentContainsCalculatedBillingRatesHaCharges()
    {
        $doc = $this->shipmentDocument->first();
        $billingRates = collect($doc->calculated_billing_rates);
        $billingRates->each(function($billingRate) {
            $this->assertEquals($billingRate['charges'], 0);
        });
    }

    /**
     * @Then :documentCount shipment document for order number :orderNumber is generated
     */
    public function shipmentDocumentForOrderNumberIsGenerated($documentCount, $orderNumber)
    {
        $shipmentDocument = ShipmentCacheDocument::whereRaw(['order.number'=> $orderNumber])->get();
        $this->shipmentDocument = $shipmentDocument;
        $this->assertEquals($documentCount, $this->shipmentDocument->count());
    }

    /**
     * @Then shipment document contains :billingRateCount billing rate with :chargeQuantity as quantity charge
     */
    public function shipmentCacheDocumentContainsBillingRateWithAsQuantityCharge($billingRateCount, $chargeQuantity)
    {
        $doc = $this->shipmentDocument->first();
        $billingRates = collect($doc->calculated_billing_rates);
        $this->assertTrue($billingRates->count() == $billingRateCount);

        $filterBillingRates = $billingRates->filter(function($element) use($chargeQuantity){
            return $element['charges'] == (int)$chargeQuantity;
        });

        $this->assertTrue($filterBillingRates->isNotEmpty());
    }

    /**
     * @Given mail component is enable to send email to :email
     */
    public function mailComponentIsEnable($email): void
    {
        $this->enableMailing($email, true);
    }

    /**
     * @param Collection $charges
     * @param string $description
     * @param string $amount
     * @param string $quantity
     * @param string $count
     * @return void
     */
    private function checkShippingDocumentForFees(Collection $charges, string $description, string $amount, string $quantity, string $count): void
    {
        $chargedItems = $charges->where('description', '=', $description)
            ->where('total_charge', '=', (float)$amount)
            ->where('quantity', '=', (int)$quantity);

        $this->assertTrue(!$chargedItems->isEmpty(), 'There are no fees matching the criteria.');
        $this->assertEquals($count, $chargedItems->count(), 'There are not exactly ' . $count . ' fees with matching the criteria.');
    }

    public function enableMailing(string $email, bool $enable = false): void
    {
        $mock = $this->getMockBuilder(MailComponent::class)
            ->onlyMethods(['sendEmail', 'send', 'buildEmail'])
            ->getMock();

        $mock->expects($this->once())
            ->method('send')
            ->with($this->equalTo($email), $this->anything())
            ->willReturn(true);

        if ($enable) {
            $mock->expects($this->once())
                ->method('sendEmail')
                ->with(
                    $this->equalTo($email),
                    $this->equalTo(InvoiceCalculationDone::class),
                    $this->anything(),
                );

            $mock->expects($this->once())
                ->method('buildEmail')
                ->with(
                    $this->equalTo(InvoiceCalculationDone::class),
                    $this->anything(),
                );
        }

        $this->mailComponent = $mock;

    }

    public function disableMailing(): void
    {
        $mock = $this->getMockBuilder(MailComponent::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sendEmail'])
            ->getMock();

        $mock->expects($this->once())
            ->method('sendEmail')->with(
                $this->anything(),
                $this->equalTo(InvoiceCalculationDone::class),
                $this->anything(),
            )
            ->willReturn(false);

        $this->mailComponent = $mock;
    }

    /**
     * @When invoice email was send
     */
    public function emailWasSend()
    {
        //no error occur so far
        $this->assertEmpty($this->getActualOutput(), 'No errors occurred so far.');
    }

    /**
     * @Then I should get an error :message
     */
    public function iShouldGetAnError($message)
    {
        $this->assertTrue(strpos($this->errorMessage, $message) != false);
    }

    /**
     * @Then I should not have gotten the error :arg1
     */
    public function iShouldNotHaveGottenTheError($arg1)
    {
        $this->assertEmpty($this->rateCardError);
    }

    /**
     * @Then batch bills for :threePlName have status :status
     */
    public function batchBillsForHaveStatus($threePlName, $status)
    {
        /** @var Customer $customer */
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$threePlName) {
            $query->where('name', $threePlName);
        })->firstOrFail();

        $batchBill = BulkInvoiceBatch::where(['customer_id' => $customer->id])
            ->first() ?? null;
        $this->assertInstanceOf(BulkInvoiceBatch::class, $batchBill);
        $this->assertEquals(InvoiceStatus::from($status), $batchBill->getBulkInvoiceBatchStatus());
    }
}
