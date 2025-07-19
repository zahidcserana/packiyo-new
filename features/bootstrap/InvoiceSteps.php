<?php


use App\Components\BillableOperationService;
use App\Components\BillingRates\Charges\StorageByLocation\StorageByLocationChargeComponent;
use App\Components\BillingRates\Processors\PackagingBillingRateCacheProcessor;
use App\Components\BillingRates\Processors\PickingBillingRateCacheProcessor;
use App\Components\BillingRates\Processors\ShippingBillingRateCacheProcessor;
use App\Components\BillingRates\PackagingRateBillingRateComponent;
use App\Components\BillingRates\ShipmentsByPickingBillingRateComponentV2;
use App\Components\BillingRates\ShipmentsByShippingLabelBillingRateComponent;
use App\Components\BillingRates\StorageByLocationBillingRateComponent;
use App\Components\BillingRates\StorageByLocationRate\MongoDbConnectionTester;
use App\Components\FulfillmentBillingCalculatorService;
use App\Components\InventoryLogComponent;
use App\Components\Invoice\InvoiceProcessor;
use App\Components\Invoice\MongoInvoiceGenerator;
use App\Components\Invoice\Strategies\InvoiceLegacyStrategy;
use App\Components\Invoice\Strategies\InvoiceMongoStrategy;
use App\Components\InvoiceComponent;
use App\Components\InvoiceExportComponent;
use App\Components\PurchaseOrderBillingCacheComponent;
use App\Components\ReceivingBillingCalculatorComponent;
use App\Components\ShipmentBillingCacheService;
use App\Enums\InvoiceStatus;
use App\Features\Wallet;
use App\Http\Requests\Invoice\BatchStoreRequest;
use App\Jobs\Billing\BulkInvoiceBatchSuccessJob;
use App\Jobs\Billing\CalculateBulkInvoiceBatchJob;
use App\Jobs\Billing\GenerateCsvJob;
use App\Jobs\Billing\InvoiceGenerationOnTheFlyJob;
use App\Jobs\Billing\InvoiceGenerationSuccessJob;
use App\Jobs\Billing\InvoiceSummaryJob;
use App\Jobs\Billing\RecalculateInvoiceJob;
use App\Models\BulkInvoiceBatch;
use App\Models\CacheDocuments\InvoiceCacheDocument;
use App\Models\CacheDocuments\ShippingLabelRateShipmentCacheDocument;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Order;
use App\Models\Shipment;
use Behat\Behat\Tester\Exception\PendingException;
use Carbon\Carbon;
use Illuminate\Bus\Batch;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Testing\Fakes\PendingBatchFake;
use Illuminate\Testing\TestResponse;

trait InvoiceSteps
{
    public Collection|null $invoiceCacheDocument = null;
    public $batchInstance;


    /**
     * @Then the invoice generation on the fly job is executed in the background
     */
    public function theInvoiceGenerationOnTheFlyJobIsExecutedInTheBackground()
    {
        $jobs = $this->getJobsToExecute(InvoiceGenerationOnTheFlyJob::class);
        $job = reset($jobs);
        $job->handle(app(MongoInvoiceGenerator::class));
        $this->expectsJobs(InvoiceGenerationSuccessJob::class);
        $this->expectsJobs(InvoiceGenerationSuccessJob::class);
    }

    /**
     * @Then the invoice generation on the fly job is executed in the background but with error during jobs
     */
    public function theInvoiceGenerationOnTheFlyJobIsExecutedInTheBackgroundButWithErroDuringJobs()
    {
        $jobs = $this->getJobsToExecute(InvoiceGenerationOnTheFlyJob::class);
        $job = reset($jobs);
        $job->handle(app(MongoInvoiceGenerator::class));
    }

    /**
     * @Then the invoice generation success is executed in the background
     */
    public function theInvoiceGenerationSuccessIsExecutedInTheBackground()
    {
        $jobs = $this->getJobsToExecute(InvoiceGenerationSuccessJob::class);
        $job = reset($jobs);
        $job->handle(app(InvoiceComponent::class));
    }

    /**
     * @Then the invoice summary generation is executed in the background
     */
    public function theInvoiceSummaryGenerationSuccessIsExecutedInTheBackground()
    {
        $invoiceGenerationSuccess = $this->dispatchedJobs[3];
        $invoiceGenerationSuccess->handle();
    }

    /**
     * @Then the invoice status is :status
     */
    public function theInvoiceStatusIs($status)
    {
        $jobs = $this->getJobsToExecute(InvoiceGenerationSuccessJob::class);
        if (empty($jobs)) {
            $jobs = $this->getJobsToExecute(InvoiceGenerationOnTheFlyJob::class);
        }
        $job = reset($jobs);
        $this->assertTrue(InvoiceStatus::from($status) == $job->invoice->status);
    }

    /**
     * @Then the invoice generated on the fly should have :invoiceLineItemCount invoice items
     */
    public function theInvoiceGeneratedOnTheFlyShouldHaveInvoiceLineItems($invoiceLineItemCount): void
    {
        $calculateInvoiceJob = $this->dispatchedJobs[1];
        $this->assertCount($invoiceLineItemCount, $calculateInvoiceJob->invoice->invoiceLineItems);
    }

    /**
     * @Then :documentCount invoice cache documents are generated between :period_start and :period_end
     */
    public function invoiceCacheDocumentsAreGenerated($documentCount, $period_start, $period_end)
    {
        $period_start = (new Carbon($period_start))->toDateString();
        $period_end = (new Carbon($period_end))->toDateString();
        $invoiceCacheDocument = InvoiceCacheDocument::whereRaw(['period_start' => $period_start, 'period_end' => $period_end])->get();
        $this->invoiceCacheDocument = $invoiceCacheDocument;
        $invoiceCacheDocumentCount = $invoiceCacheDocument->count();
        $this->assertEquals($documentCount, $invoiceCacheDocumentCount);
    }

    /**
     * @Then the invoice cache was validated
     * @description checks if valid_at property was set, confirming that all documents are available to move foward
     */
    public function invoiceCacheWasValidated()
    {
        $invoiceDocument = $this->invoiceCacheDocument->first();
        $this->assertNotEmpty($invoiceDocument->valid_at, "Invoice event was not send");
    }

    /**
     * @Then the invoice cache was not validated
     * @description checks if valid_at property was set, confirming that all documents are available to move foward
     */
    public function invoiceCacheWasNotValidated()
    {
        $invoiceDocument = $this->invoiceCacheDocument->first();
        $this->assertEmpty($invoiceDocument->valid_at, "Invoice event was send");
    }

    /**
     * @When we lost the invoice cache document
     */
    public function weLostTheInvoiceCacheDocument()
    {
        $invoiceDocument = $this->invoiceCacheDocument->first();
        $invoiceDocument->delete();
    }

    /**
     * @When we lost the shipping charge cache for the order number :orderNumber
     */
    public function weLostTheShippingChargeCacheForTheOrderNumber(string $orderNumber)
    {
        $order = Order::where(['number' => $orderNumber])->firstOrFail();
        $shippingCacheDocument = ShippingLabelRateShipmentCacheDocument::whereRaw(['order_id' => $order->id])->first();
        $shippingCacheDocument->delete();
    }


    /**
     * @Then the invoice cache contains :billingRateCount billing rate
     */
    public function theInvoiceCacheContainsBillingRate($billingRateCount)
    {
        $invoiceCacheDocument = $this->invoiceCacheDocument->first();
        $billingRates = collect($invoiceCacheDocument->billing_rates);
        $this->assertEquals($billingRateCount, $billingRates->count());
    }

    /**
     * @return void
     */
    private function mockInvoiceProcessor(): void
    {
        $mongoTesterMock = $this->getMockBuilder(MongoDbConnectionTester::class)
            ->onlyMethods(['testConnection'])
            ->getMock();

        $mongoTesterMock->expects($this->any())
            ->method('testConnection')
            ->willReturn(false);
        $fulfillmentService = new FulfillmentBillingCalculatorService(
                new PickingBillingRateCacheProcessor,
                new ShippingBillingRateCacheProcessor,
                new PackagingBillingRateCacheProcessor
            );

        $InvoiceProcessorMock = new InvoiceProcessor(
            new InvoiceLegacyStrategy(
                new InvoiceExportComponent,
                new StorageByLocationBillingRateComponent,
                new ShipmentsByPickingBillingRateComponentV2,
                new ShipmentsByShippingLabelBillingRateComponent,
                new PackagingRateBillingRateComponent
            ),
            new InvoiceMongoStrategy,
            $mongoTesterMock,
            new BillableOperationService(
                new ShipmentBillingCacheService,
                $fulfillmentService,
                new PurchaseOrderBillingCacheComponent,
                new ReceivingBillingCalculatorComponent,
                new StorageByLocationChargeComponent(
                    new InventoryLogComponent
                )
            ),
            new InventoryLogComponent
        );

        $this->app->bind(InvoiceProcessor::class, function ($app) use ($InvoiceProcessorMock) {
            return $InvoiceProcessorMock;
        });
    }


    /**
     * TODO use on storage charges feature scenarios
     * @When the invoice is calculated in the background with additional jobs on the background
     */
    public function theInvoiceIsCalculatedInTheBackgroundWithAdditional(): void
    {
        $calculateInvoiceJob = $this->dispatchedJobs[0];
        $calculateInvoiceJob->handle(app(InvoiceProcessor::class), app(InvoiceComponent::class));
        if ($this->getCustomerInScope()->parent->hasFeature(Wallet::class)) {
            $this->expectsJobs(InvoiceGenerationOnTheFlyJob::class); // Puts job in dispatchedJobs but it is not handled.
        }
    }

    /**
     * @When the invoice is calculated in the background with no additional jobs
     */
    public function theInvoiceIsCalculatedInTheBackgroundWithNoAdditionalJobs(): void
    {
        $calculateInvoiceJob = $this->dispatchedJobs[0];
        $calculateInvoiceJob->handle(app(InvoiceProcessor::class), app(InvoiceComponent::class));
    }

    /**
     * @When the Mongo db service is not available
     */
    public function theMongoDbServiceIsNotAvailable()
    {
        $this->mockInvoiceProcessor();
    }

    public function getJobsToExecute(string $instanceOfClass): array
    {
        return array_filter($this->dispatchedJobs, function ($element) use ($instanceOfClass) {
            return $element instanceof $instanceOfClass;
        });
    }

    /**
     * @Then a new invoice is generated with status :status
     */
    public function aNewInvoiceIsGeneratedForThePeriodWithStatus($status)
    {
        $invoice = Invoice::where('status', InvoiceStatus::from($status))
            ->first() ?? null;

        $this->assertInstanceOf(Invoice::class, $invoice);
    }

    /**
     * @Then bulk invoice for :customerName batch with status :status
     */
    public function bulkInvoiceForBatchWithStatus($customerName, $status)
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $bulkInvoiceBatch = BulkInvoiceBatch::where('customer_id', $customer->id)
            ->firstOrFail();

        $this->assertEquals(InvoiceStatus::from($status), $bulkInvoiceBatch->getBulkInvoiceBatchStatus());
    }


    /**
     * @When no invoice for 3pl :customerName or clients
     */
    public function noInvoiceForPlOrClients($customerName)
    {
        /** @var Customer $customer */
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $children = $customer->children()->get();
        $result = [];
        $result[] = Invoice::where(['customer_id' => $customer->id])->first() ?? null;
        foreach ($children as $child) {
            $result[] = Invoice::where(['customer_id' => $child->id])->first() ?? null;
        }
        $filteredArray = array_filter($result, function ($value) {
            return !is_null($value);
        });
        $this->assertEmpty($filteredArray, "At least one invoice was generated");
    }


    /**
     * @Then a new invoice is generated for 3pl client :customerName for the period :startDate to :endDate with status :status
     */
    public function aNewInvoiceIsGeneratedForPlClientwithStatus($customerName, $startDate, $endDate, $status)
    {
        /** @var Customer $customer */
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $from = Carbon::parse($startDate);
        $to = Carbon::parse($endDate);

        $invoice = Invoice::where(['customer_id' => $customer->id])
            ->whereBetween('period_start', [$from, $to])
            ->firstOrFail();
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals(InvoiceStatus::from($status), $invoice->getInvoiceStatus());
    }


    /**
     * @Given An invoice was calculated for client :customerName for the period :startDate to :endDate
     */
    public function anInvoiceWasCalculatedForClientForThePeriodTo(string $customerName, string $startDate, string $endDate): void
    {
        $threePL = $this->getCustomerInScope();

        if (!$threePL->is3pl()) {
            throw new PendingException("TODO: The customer in scope is not a 3PL.");
        }

        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'period_start' => Carbon::parse($startDate),
            'period_end' => Carbon::parse($endDate),
            'calculated_at' => Carbon::parse($endDate)->addDay()
        ]);
        $rateCard = $customer->primaryRateCard();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'number' => '123456789'
        ]);

        $shipment = Shipment::factory()->create(['order_id' => $order->id]);
        $description = 'Order: ' . $shipment->order->number . ', TN: ' . $shipment->getFirstTrackingNumber() . ' | test';

        InvoiceLineItem::factory()->create([
            'invoice_id' => $invoice->id,
            'billing_rate_id' => $rateCard->billingRates()->first()->id,
            'description' => $description,
            'quantity' => 1,
            'charge_per_unit' => 1.0,
            'total_charge' => 1 * 1.0,
            'period_end' => $invoice->period_end,
            'shipment_id' => $shipment->id
        ]);
    }

    /**
     * @Given the latest invoice for client :customerName was finalized
     */
    public function theLatestInvoiceForClientWasFinalized(string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $invoice = Invoice::where(['customer_id' => $customer->id])->latest()->firstOrFail();
        $invoice->invoice_number = '1234';
        $invoice->due_date = $invoice->calculated_at->addWeek();
        $invoice->is_finalized = true;
    }

    /**
     * @When I export the latest invoice for customer :customerName
     */
    public function iExportTheLatestInvoiceForCustomer(string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $user = $customer->parent->users()->first();
        Auth::shouldReceive('user')
            ->atLeast(1)
            ->andReturn($user);
        $invoice = Invoice::where(['customer_id' => $customer->id])->latest()->firstOrFail();
        $invoiceComponent = new InvoiceExportComponent();
        $this->expectsJobs(GenerateCsvJob::class); // Puts job in dispatchedJobs but it is not handled.
        $response = $invoiceComponent->generateCsv($invoice); // Dispatches CalculateInvoiceJob.
        $response = TestResponse::fromBaseResponse($response); // A workaround to test HTTP requests.
        $response->assertRedirect('/'); // Redirect back.
        $this->assertCount(1, $this->dispatchedJobs);
    }

    /**
     * @When the invoice is exported in the background
     */
    public function theInvoiceIsExportedInTheBackground(): void
    {
        $exportInvoiceJob = $this->dispatchedJobs[0];
        $exportInvoiceJob->handle(app(InvoiceProcessor::class), app(InvoiceComponent::class));
    }


    /**
     * @When I calculate invoice for 3pl :threePlName clients for the period :startDate to :endDate
     */
    public function iCalculateInvoiceForPlClientsForThePeriodTo($threePlName, $startDate, $endDate)
    {
        Carbon::setTestNow();//reset time to present
        /** @var Customer $threePlCustomer */
        $threePlCustomer = Customer::whereHas('contactInformation', function (Builder $query) use (&$threePlName) {
            $query->where('name', $threePlName);
        })->firstOrFail();

        $clientAmounts = $threePlCustomer->children->count();

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
        Bus::fake();

        $invoiceComponent = new InvoiceComponent($invoiceExportComponent, $mailComponent);
        $response = $invoiceComponent->batchStore($request, $threePlCustomer, auth()->user()); // Dispatches CalculateInvoiceJob.
        $response = TestResponse::fromBaseResponse($response); // A workaround to test HTTP requests.
        $response->assertRedirect(route('billings.customer_invoices', ['customer' => $threePlCustomer->id]));

        Bus::assertBatched(function (PendingBatchFake $batch) use ($threePlCustomer, $clientAmounts) {
            // Check that the batch contains the correct number of jobs
            $this->assertCount($clientAmounts, $batch->jobs); // Adjust the count to match your number of jobs

            $this->batchInstance = $batch;
            // You can also inspect the jobs in the batch
            foreach ($batch->jobs as $job) {
                $this->assertInstanceOf(CalculateBulkInvoiceBatchJob::class, $job);
                $this->dispatchedJobs[] = $job;
            }
            return true;
        });
    }

    /**
     * @Then no invoice cache document is generated
     */
    public function noInvoiceCacheDocumentIsGenerated()
    {
        $docs = InvoiceCacheDocument::all()->count();
        $this->assertTrue($docs == 0);
    }

    /**
     * @When invoices are calculated in the background
     */
    public function invoicesAreCalculatedInTheBackground()
    {
        #way to completed, but it works
        $dispatchSubJobs = [];
        $endJob = [];
        $assertBulkStatus = true;
        foreach ($this->dispatchedJobs as $job) {
            Log::debug('handling job invoice ID: ' . $job->invoice->id);
            // Handle the job, which may dispatch other jobs
            $job->handle();
            Bus::assertDispatched(InvoiceGenerationOnTheFlyJob::class, function ($subJobGen) use (&$dispatchSubJobs) {
                // Add jobs for next dispatch
                $dispatchSubJobs[] = $subJobGen;
                return true;
            });

            $bulkInvoiceBatch = BulkInvoiceBatch::find($job->invoice->bulkInvoiceBatch()->first()->id);
            if ($assertBulkStatus && count($this->dispatchedJobs) > 1) {
                //asserting status
                $this->assertEquals(InvoiceStatus::CALCULATING_STATUS, $bulkInvoiceBatch->getBulkInvoiceBatchStatus());
                $assertBulkStatus = false;
            }
        }

        if ($this->batchInstance) {
            $user = auth()->user();
            BulkInvoiceBatchSuccessJob::dispatch($bulkInvoiceBatch, $user);
        }

        foreach ($dispatchSubJobs as $subJob) {
            $finalJobs = [];
            $subJob->handle(app(MongoInvoiceGenerator::class));
            Bus::assertDispatched(InvoiceGenerationSuccessJob::class, function ($subJob) use (&$finalJobs) {
                // Add jobs for next dispatch
                $finalJobs[] = $subJob; // Add new jobs to the list
                return true;
            });
            Bus::assertDispatched(InvoiceSummaryJob::class, function ($subJob) use (&$finalJobs) {
                // Add jobs for next dispatch
                $finalJobs[] = $subJob; // Add new jobs to the list
                return true;
            });

            foreach ($finalJobs as $job) {
                if($job instanceof InvoiceGenerationSuccessJob){
                    $job->handle(app(InvoiceComponent::class));
                }else{
                    $job->handle();
                }
            }
        }
        Bus::assertDispatched(BulkInvoiceBatchSuccessJob::class, function ($job) use (&$endJob) {
            // You can add additional checks here, e.g., whether the job received the right parameters
            $endJob[] = $job;
            return true;
        });
        foreach ($endJob as $job) {
            $job->handle();
        }
    }


    /**
     * @When invoices are calculated in the background with no wallet feature
     */
    public function invoicesAreCalculatedInTheBackgroundWithNoWalletFeature()
    {
        $endJob = [];
        $assertBulkStatus = true;
        foreach ($this->dispatchedJobs as $job) {
            // Handle the job, which may dispatch other jobs
            $job->handle();
            if ($assertBulkStatus && count($this->dispatchedJobs) > 1) {
                //asserting status
                $bulkInvoiceBatch = BulkInvoiceBatch::find($job->invoice->bulkInvoiceBatch()->first()->id);
                $this->assertEquals(InvoiceStatus::CALCULATING_STATUS, $bulkInvoiceBatch->getBulkInvoiceBatchStatus());
                $assertBulkStatus = false;
            }
        }

        if ($this->batchInstance) {
            $user = auth()->user();
            BulkInvoiceBatchSuccessJob::dispatch($bulkInvoiceBatch, $user);
        }

        Bus::assertDispatched(BulkInvoiceBatchSuccessJob::class, function ($job) use (&$endJob) {
            // You can add additional checks here, e.g., whether the job received the right parameters
            $endJob[] = $job;
            return true;
        });

        foreach ($endJob as $job) {
            $job->handle();
        }
    }

    /**
     * @When invoices are calculated in the background only first job is a success
     */
    public function invoicesAreCalculatedInTheBackgroundWithOneJobFailing()
    {
        $jobMock = Mockery::mock(InvoiceGenerationOnTheFlyJob::class);

        // Step 2: Set up the expectation that the 'handle' method will throw an exception
        $jobMock->shouldReceive('handle')
            ->andThrow(new Exception('Test exception from handle method'));

        #way to completed, but it works
        $dispatchSubJobs = [];
        foreach ($this->dispatchedJobs as $job) {
            $job->handle();

            $bulkInvoiceBatch = BulkInvoiceBatch::find($job->invoice->bulkInvoiceBatch()->first()->id);
            Bus::assertDispatched(InvoiceGenerationOnTheFlyJob::class, function ($subJob) use (&$dispatchSubJobs) {
                $dispatchSubJobs[] = $subJob; // Add new jobs to the list
                return true;
            });
        }

        if ($this->batchInstance) {
            $user = auth()->user();
            BulkInvoiceBatchSuccessJob::dispatch($bulkInvoiceBatch, $user);
        }

        $counter = 1;

        foreach ($dispatchSubJobs as $subJob) {

            if ($counter != 1) {
                $invoice = Invoice::find($subJob->invoice->id);
                $invoice->setInvoiceStatus(InvoiceStatus::FAILED_STATUS);
                continue;
            }

            $finalJobs = [];
            $subJob->handle(app(MongoInvoiceGenerator::class));
            Bus::assertDispatched(InvoiceGenerationSuccessJob::class, function ($subJob) use (&$finalJobs) {
                $finalJobs[] = $subJob; // Add new jobs to the list
                return true;
            });
            Bus::assertDispatched(InvoiceSummaryJob::class, function ($subJob) use (&$finalJobs) {
                $finalJobs[] = $subJob; // Add new jobs to the list
                return true;
            });

            foreach ($finalJobs as $job) {
                if($job instanceof InvoiceGenerationSuccessJob){
                    $job->handle(app(InvoiceComponent::class));
                }else{
                    $job->handle();
                }
            }
            $counter++;
        }

        Bus::assertDispatched(BulkInvoiceBatchSuccessJob::class, function ($job) use (&$endJob) {
            // You can add additional checks here, e.g., whether the job received the right parameters
            $endJob[] = $job;
            return true;
        });

        foreach ($endJob as $job) {
            $job->handle();
        }
    }

    /**
     * @When invoices are calculated in the background with no wallet feature only first job is a success
     */
    public function invoicesAreCalculatedInTheBackgrounWithNoWalletFeaturedWithOneJobFailing()
    {
        $jobMock = Mockery::mock(InvoiceGenerationOnTheFlyJob::class);

        // Step 2: Set up the expectation that the 'handle' method will throw an exception
        $jobMock->shouldReceive('handle')
            ->andThrow(new Exception('Test exception from handle method'));

        $endJob = [];
        foreach ($this->dispatchedJobs as $job) {
            $invoice = Invoice::find($job->invoice->id);
            $invoice->setInvoiceStatus(InvoiceStatus::FAILED_STATUS);
            $bulkInvoiceBatch = BulkInvoiceBatch::find($job->invoice->bulkInvoiceBatch()->first()->id);
        }
        if ($this->batchInstance) {
            $user = auth()->user();
            BulkInvoiceBatchSuccessJob::dispatch($bulkInvoiceBatch, $user);
        }

        Bus::assertDispatched(BulkInvoiceBatchSuccessJob::class, function ($job) use (&$endJob) {
            // You can add additional checks here, e.g., whether the job received the right parameters
            $endJob[] = $job;
            return true;
        });

        foreach ($endJob as $job) {
            $job->handle();
        }
    }

    /**
     * @When invoices are calculated in the background all jobs are a failure
     */
    public function invoicesAreCalculatedInTheBackgroundAllJobsAreAFailure()
    {

        $jobMock = Mockery::mock(InvoiceGenerationOnTheFlyJob::class);

        // Step 2: Set up the expectation that the 'handle' method will throw an exception
        $jobMock->shouldReceive('handle')
            ->andThrow(new Exception('Test exception from handle method'));

        #way to completed, but it works
        $dispatchSubJobs = [];
        foreach ($this->dispatchedJobs as $job) {
            $job->handle();
            Bus::assertDispatched(InvoiceGenerationOnTheFlyJob::class, function ($subJob) use (&$dispatchSubJobs) {
                $dispatchSubJobs[] = $subJob; // Add new jobs to the list
                return true;
            });
            $bulkInvoiceBatch = BulkInvoiceBatch::find($job->invoice->bulkInvoiceBatch()->first()->id);
        }

        if ($this->batchInstance) {
            $user = auth()->user();
            BulkInvoiceBatchSuccessJob::dispatch($bulkInvoiceBatch, $user);
        }

        foreach ($dispatchSubJobs as $subJob) {
            $invoice = Invoice::find($subJob->invoice->id);
            $invoice->setInvoiceStatus(InvoiceStatus::FAILED_STATUS);
        }

        Bus::assertDispatched(BulkInvoiceBatchSuccessJob::class, function ($job) use (&$endJob) {
            // You can add additional checks here, e.g., whether the job received the right parameters
            $endJob[] = $job;
            return true;
        });

        foreach ($endJob as $job) {
            $job->handle();
        }
    }

    /**
     * @When invoices are calculated in the background with no wallet feature all jobs are a failure
     */
    public function invoicesAreCalculatedInTheBackgroundWithNoWalletFeatureAllJobsAreAFailure()
    {

        $jobMock = Mockery::mock(InvoiceGenerationOnTheFlyJob::class);
        // Step 2: Set up the expectation that the 'handle' method will throw an exception
        $jobMock->shouldReceive('handle')
            ->andThrow(new Exception('Test exception from handle method'));

        #way to completed, but it works
        $endJob = [];
        foreach ($this->dispatchedJobs as $job) {
            $bulkInvoiceBatch = BulkInvoiceBatch::find($job->invoice->bulkInvoiceBatch()->first()->id);
            $invoice = Invoice::find($job->invoice->id);
            $invoice->setInvoiceStatus(InvoiceStatus::FAILED_STATUS);
        }

        if ($this->batchInstance) {
            $user = auth()->user();
            BulkInvoiceBatchSuccessJob::dispatch($bulkInvoiceBatch, $user);
        }

        Bus::assertDispatched(BulkInvoiceBatchSuccessJob::class, function ($job) use (&$endJob) {
            // You can add additional checks here, e.g., whether the job received the right parameters
            $endJob[] = $job;
            return true;
        });
        foreach ($endJob as $job) {
            $job->handle();
        }
    }

    /**
     * @When invoices are calculated in the background with no wallet feature and we loss a record
     */
    public function invoicesAreCalculatedInTheBackgroundWithNoWalletFeatureAndWeLossARecord()
    {
        $endJob = [];
        $assertBulkStatus = true;
        foreach ($this->dispatchedJobs as $job) {
            // Handle the job, which may dispatch other jobs
            $job->handle();
            if ($assertBulkStatus && count($this->dispatchedJobs) > 1) {
                //asserting status
                $bulkInvoiceBatch = BulkInvoiceBatch::find($job->invoice->bulkInvoiceBatch()->first()->id);
                $this->assertEquals(InvoiceStatus::CALCULATING_STATUS, $bulkInvoiceBatch->getBulkInvoiceBatchStatus());
                $assertBulkStatus = false;
            }
        }

        if ($this->batchInstance) {
            $user = auth()->user();
            BulkInvoiceBatchSuccessJob::dispatch($bulkInvoiceBatch, $user);
        }

        Bus::assertDispatched(BulkInvoiceBatchSuccessJob::class, function ($job) use (&$endJob) {
            // You can add additional checks here, e.g., whether the job received the right parameters
            $endJob[] = $job;
            return true;
        });
        foreach ($endJob as $job) {
            $job->batch->bulkInvoiceBatchInvoices()->get()->each->delete();
            $job->handle();
        }
    }

    /**
     * @When the invoice spent :value days without being calculated
     */
    public function theInvoiceSpentDaysWithoutBeingCalculated($value)
    {
        $invoice = Invoice::latest()->first();
        $date = $invoice->created_at;
        $date->subDays($value);
        $invoice->created_at = $date;
        $invoice->save();
    }


    /**
     * @Then invoice has status :value
     */
    public function invoiceHasStatus($value)
    {
        $invoice = Invoice::latest()->first();
        $this->assertEquals(InvoiceStatus::from($value), $invoice->getInvoiceStatus());
    }

    /**
     * TODO use on storage charges feature scenarios
     * @When I recalculate invoice in the background
     */
    public function iRecalculateInvoiceInTheBackground()
    {
        $oldInvoice = Invoice::latest()->first();

        $invoiceExportComponent = \Mockery::mock(InvoiceExportComponent::class);

        if (empty($this->mailComponent)) {
            $this->disableMailing();
        }

        $mailComponent = $this->mailComponent;
        $invoiceComponent = new InvoiceComponent($invoiceExportComponent, $mailComponent);
        $this->expectsJobs(RecalculateInvoiceJob::class);
        $invoiceComponent->recalculate($oldInvoice, auth()->user());
        $jobs = $this->getJobsToExecute(RecalculateInvoiceJob::class);
        $job = reset($jobs);
        $job->handle(app(InvoiceProcessor::class), $invoiceComponent); // in order to work with dependency injection we are adding stuff here
    }

    /**
     * @Then the invoice should be a recalculation
     */
    public function theInvoiceShouldBeARecalculation()
    {
        $invoice = Invoice::latest()->first();
        $this->assertTrue(!is_null($invoice->recalculated_from_invoice_id));
    }

    /**
     * @Then the old invoice should be deleted
     */
    public function theOldInvoiceShouldBeDeleted()
    {
        $invoice = Invoice::latest()->first();
        $oldInvoice = $invoice->recalculatedInvoice()->withTrashed()->first();
        $this->assertTrue(!is_null($oldInvoice->deleted_at));
    }

    /**
     * @When the invoice is calculated in the background
     */
    public function theInvoiceIsCalculatedInTheBackground(): void
    {
        $calculateInvoiceJob = $this->dispatchedJobs[0];
        $calculateInvoiceJob->handle(app(InvoiceProcessor::class), app(InvoiceComponent::class));
    }

    /**
     * @Then the invoice should not have any invoice items
     */
    public function theInvoiceShouldHaveNoInvoiceLineItems(): void
    {
        $calculateInvoiceJob = $this->dispatchedJobs[0];
        $this->assertEmpty($calculateInvoiceJob->invoice->invoiceLineItems);
    }

    /**
     * @Then the invoice should have :invoiceLineItemCount invoice items
     */
    public function theInvoiceShouldHaveInvoiceLineItems($invoiceLineItemCount)
    {
        $calculateInvoiceJob = $this->dispatchedJobs[0];
        $this->assertCount($invoiceLineItemCount, $calculateInvoiceJob->invoice->invoiceLineItems);
    }

}
