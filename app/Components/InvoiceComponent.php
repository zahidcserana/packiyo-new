<?php

namespace App\Components;

use App\Components\BillingRates\BillingRateInterface;
use App\Features\Wallet;
use App\Jobs\Billing\BulkInvoiceBatchSuccessJob;
use App\Jobs\Billing\CalculateBulkInvoiceBatchJob;
use App\Traits\InvoiceTrait;
use App\Exceptions\BillingRateException;
use App\Exceptions\InvoiceFinalizedException;
use App\Exceptions\InvoiceGenerationException;
use App\Jobs\Billing\RecalculateInvoiceJob;
use App\Mail\InvoiceCalculationDone;
use Exception;
use App\Enums\InvoiceStatus;
use Illuminate\Bus\Batch;
use App\Http\Requests\{Invoice\AdHocBillingRequest,
    Invoice\BatchStoreRequest,
    Invoice\StoreRequest,
    Invoice\UpdateAdHocBillingRequest};
use App\Jobs\Billing\CalculateInvoiceJob;
use App\Models\{BillingBalance,
    Invoice,
    BillingRate,
    BulkInvoiceBatch,
    InvoiceLineItem,
    Customer,
    InventoryChange,
    PurchaseOrder,
    Return_,
    Shipment,
    User};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\{Arr, Facades\Bus, Facades\DB};
use App\Exceptions\BillingException;
use Illuminate\Support\Facades\Log;
use Throwable;

class InvoiceComponent extends BaseComponent
{
    use InvoiceTrait;
    protected InvoiceExportComponent $invoiceExport;
    protected array $billingRateComponents;
    protected array $ratesThatTrackBilledOperations;

    protected MailComponent $mailComponent;

    public function __construct(
        InvoiceExportComponent $invoiceExport,
        MailComponent $mailComponent,
        BillingRateInterface ...$billingRateComponents,
    )
    {
        $this->invoiceExport = $invoiceExport;
        $this->mailComponent = $mailComponent;
        $this->billingRateComponents = [];
        $this->ratesThatTrackBilledOperations = [];

        foreach ($billingRateComponents as $billingRateComponent) {
            $this->billingRateComponents[$billingRateComponent::$rateType] = $billingRateComponent;

            if ($billingRateComponent->tracksBilledOperations()) {
                $this->ratesThatTrackBilledOperations[] = $billingRateComponent;
            }
        }
    }

    /**
     * @param StoreRequest $request
     * @param User|null $user
     * @return mixed
     * @throws BillingException
     */
    public function store(StoreRequest $request, ?User $user = null)
    {
        $input = $request->all();
        $customer = Customer::find($input['customer_id']);

        $input['start_date'] = Carbon::parse($input['start_date'])->startOfDay();
        $input['end_date'] = Carbon::parse($input['end_date'])->endOfDay();
        $invoice = $this->generateInvoice($customer, $input);
        $invoice->setInvoiceStatus(InvoiceStatus::PENDING_STATUS);

        CalculateInvoiceJob::dispatch($invoice, $user);

        return redirect()
            ->route('billings.customer_invoices',['customer' => $customer])
            ->withStatus(__('Invoice successfully created.'));
    }

    /**
     * @param BatchStoreRequest $request
     * @param Customer $threePlCustomer
     * @param User|null $user
     * @return mixed
     * @throws BillingException
     * @throws Throwable
     */
    public function batchStore(BatchStoreRequest $request, Customer $threePlCustomer , ?User $user = null)
    {
        $input = $request->all();
        if(!$threePlCustomer->is3pl()){
            throw new BillingException("Customer is not 3pl");
        }

        if(!$threePlCustomer->hasChildren()){
            throw new BillingException("Customer does not have children");
        }

        $customers = Customer::whereIn('id', $input['customer_ids'])->get();
        $missingIds = array_diff($input['customer_ids'], $customers->pluck('id')->toArray());
        if(!empty($missingIds)){
            throw new BillingException("Children ids is not found: " . implode(', ', $missingIds));
        }

        $jobs = [];
        $params = [];
        $startDate = Carbon::parse($input['start_date'])->startOfDay();
        $endDate = Carbon::parse($input['end_date'])->endOfDay();
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        $invoiceBatch = BulkInvoiceBatch::create([
            'customer_id' => $threePlCustomer->id,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'status' => InvoiceStatus::PENDING_STATUS
        ]);

        foreach ($customers as $customer) {
            $invoice = $this->generateInvoice($customer, $params);
            $invoice->setInvoiceStatus(InvoiceStatus::PENDING_STATUS);
            $invoiceBatchInvoice = null;
            DB::transaction(function () use ($invoiceBatch, $invoice , &$invoiceBatchInvoice) {
                $invoiceBatchInvoice = $invoiceBatch->bulkInvoiceBatchInvoices()->create(['invoice_id' => $invoice->id]);
            });

            if (!$invoiceBatchInvoice) {
                throw new BillingException("Failed to assign invoice to bulk invoice batch");
            }
            $jobs[] = new CalculateBulkInvoiceBatchJob($invoice, $user);
        }

        Bus::batch($jobs)
            ->allowFailures()
            ->onQueue(get_distributed_queue_name('bulkinvoice'))
            ->name('batch-invoice-3pl-customer-id-' . $threePlCustomer->id . '-' . time())
            ->finally(function (Batch $batch) use($invoiceBatch, $user) {
                BulkInvoiceBatchSuccessJob::dispatch($invoiceBatch, $user);
            })
            ->dispatch();

        return redirect()
            ->route('billings.customer_invoices',['customer' => $threePlCustomer])
            ->withStatus(__('Batch invoice successfully created.')); // need updates for correct view
    }

    /**
     * @throws BillingException
     * @throws InvoiceFinalizedException
     */
    public function recalculate(Invoice $oldInvoice, ?User $user = null): Invoice
    {
        if ($oldInvoice->is_finalized) {
            throw new InvoiceFinalizedException("Invoice finalized");
        }

        $input = [
            'start_date' => $oldInvoice->period_start,
            'end_date' => $oldInvoice->period_end,
        ];

        $oldInvoice->setInvoiceStatus(InvoiceStatus::PENDING_STATUS);
        $newInvoice = $this->generateInvoice($oldInvoice->customer, $input);
        $newInvoice->recalculated_from_invoice_id = $oldInvoice->id;
        $newInvoice->save();
        Log::debug(
            sprintf(
                "Start recalculating invoice id: %s, new invoice: %s for customer id: %s",
                $oldInvoice->id,
                $newInvoice->id,
                $oldInvoice->customer->id
            )
        );
        $oldInvoice->delete();
        RecalculateInvoiceJob::dispatch($newInvoice, $user);

        return $newInvoice;
    }

    /**
     * @throws BillingException
     */
    protected function invoiceItemsFromBalanceCharges(Invoice $invoice)
    {
        Log::channel('billing')->info('[BillingRate] Start calculating invoice items from billing balance.');

        // TODO: Use this to filter charges in the balance.
        $from = Carbon::parse($invoice->period_start);
        $to = Carbon::parse($invoice->period_end);

        // For the different warehouses.
        $balances = BillingBalance::where([
                'threepl_id' => $invoice->customer->parent_id,
                'client_id' => $invoice->customer_id
            ])
            ->get();

        if ($balances->isNotEmpty()) {
            foreach ($balances as $balance) {
                $charges = $balance->billingCharges()
                    ->whereDate('created_at', '>=', $from)
                    ->whereDate('created_at', '<=', $to)
                    ->get();

                foreach ($charges as $charge) {
                    if (!is_null($charge->billingRate)) {
                        // Just ad hoc charges - these will be redesigned.
                        $this->createInvoiceLineItem($charge->description, $invoice, $charge->billingRate, [
                            'shipment_id' => $charge->shipment_id,
                            // TODO: Fix this.
                            'fee' => $charge->amount / $charge->quantity
                        ], $charge->quantity, $charge->created_at);
                    } elseif (!is_null($charge->automation)) {
                        // Copilot-based billing charges.
                        $this->createInvoiceLineItem($charge->description, $invoice, null, [
                            'shipment_id' => $charge->shipment_id,
                            // TODO: Fix this.
                            'fee' => $charge->amount / $charge->quantity
                        ], $charge->quantity, $charge->created_at);
                    }
                }
            }
        }

        Log::channel('billing')->info('[BillingRate] End calculating invoice items from billing balance.');
    }

    protected function getRateComponent(BillingRate $rate): BillingRateInterface
    {
        if (!array_key_exists($rate->type, $this->billingRateComponents)) {
            throw new BillingException('The rate type "' . $rate->type . '" has no registered component.');
        }

        return $this->billingRateComponents[$rate->type];
    }

    public function createInvoiceLineItem($description, $invoice, $rate, $settings, $quantity, $periodEnd)
    {
        return $this->createInvoiceLineItemWithParams($description, $invoice, $rate, $settings, $quantity, $periodEnd);
    }

    public function createInvoiceLineItemOnTheFly($data)
    {
        return $this->createLineItem($data);
    }

    public function updateInvoiceLineItem($description, $invoice, $invoiceLineItem, $rate, $settings, $quantity, $periodEnd)
    {
        if (!key_exists('fee', $settings)) {
            throw new BillingException(_('The billing rate has no base fee.'));
        }

        $invoiceLineItem->invoice_id =  $invoice->id;
        $invoiceLineItem->billing_rate_id =  $rate->id;
        $invoiceLineItem->description =  $description;
        $invoiceLineItem->quantity =  $quantity;
        $invoiceLineItem->charge_per_unit =  $settings['fee'];
        $invoiceLineItem->total_charge =  $quantity * $settings['fee'];
        $invoiceLineItem->period_end =  $periodEnd;
        $invoiceLineItem->purchase_order_item_id =  $settings['purchase_order_item_id'] ?? null;
        $invoiceLineItem->purchase_order_id =  $settings['purchase_order_id'] ?? null;
        $invoiceLineItem->return_item_id =  $settings['return_item_id'] ?? null;
        $invoiceLineItem->package_id =  $settings['package_id'] ?? null;
        $invoiceLineItem->package_item_id =  $settings['package_item_id'] ?? null;
        $invoiceLineItem->shipment_id =  $settings['shipment_id'] ?? null;
        $invoiceLineItem->location_type_id =  $settings['location_type_id'] ?? null;
        $invoiceLineItem->save();

        return $invoiceLineItem;
    }

    public function checkPeriodConsistency($start, $customerId, $rateCardId)
    {
        $pass = true;

        $latestBill = Invoice::whereHas('rateCards', static function ($q) use ($rateCardId) {
            $q->where('rate_card_id', $rateCardId);
        })
            ->where('customer_id', $customerId)
            ->orderBy('period_end', 'desc')
            ->first();

        if ($latestBill) {
            $pass = Carbon::parse($start)->eq(Carbon::parse($latestBill['period_end']));
        }

        return $pass;
    }

    /**
     * @param AdHocBillingRequest $request
     * @param Invoice $invoice
     * @return void
     */
    public function adHoc(AdHocBillingRequest $request, Invoice $invoice)
    {
        $input = $request->all();
        $rate = BillingRate::find($input['billing_rate_id']);
        $settings = $rate->settings;
        $quantity = $input['quantity'];
        $periodEnd = $input['period_end'];
        $description = $rate->name;

        $this->createInvoiceLineItem($description, $invoice, $rate, $settings, $quantity, $periodEnd);

        $invoice->amount = $invoice->invoiceLineItems->sum('total_charge');
        $invoice->calculated_at = Carbon::now()->toDateTimeString();
        $invoice->save();
    }

    public function updateAdHoc(UpdateAdHocBillingRequest $request, Invoice $invoice, InvoiceLineItem $invoiceLineItem)
    {
        $input = $request->all();
        $rate = BillingRate::find($input['billing_rate_id']);
        $settings = $rate->settings;
        $quantity = $input['quantity'];
        $periodEnd = $input['period_end'];
        $description = $rate->name;

        $this->updateInvoiceLineItem($description, $invoice, $invoiceLineItem, $rate, $settings, $quantity, $periodEnd);

        $invoice->amount = $invoice->invoiceLineItems->sum('total_charge');
        $invoice->calculated_at = Carbon::now()->toDateTimeString();
        $invoice->save();
    }

    public function deleteAdHoc(InvoiceLineItem $invoiceLineItem)
    {
        $invoice = $invoiceLineItem->invoice;
        $invoice->amount = $invoice->amount - $invoiceLineItem->total_charge;
        $invoice->save();

        $invoiceLineItem->delete();
    }

    public function destroy(Invoice $invoice)
    {
        if (!$invoice->is_finalized) {
            $this->invoiceExport->deleteGeneratedCsv($invoice);
            $invoice->delete();
        }

        return $invoice;
    }

    public function search($term, $customerIds = null, $setDefaultDate = true)
    {
        $filters = json_decode($term);

        $invoicesCollection = Invoice::orderBy('created_at', 'desc');

        if ($filters->filterArray ?? false) {
            foreach ($filters->filterArray as $key => $filter) {
                if ($filter->columnName === 'dates_between') {
                    $dates = explode(" ", $filter->value);
                    $from = Arr::get($dates, '0', '');
                    $to = Arr::get($dates, '2', '');

                    if (!$setDefaultDate && empty($from)) {
                        continue;
                    }

                    // $today = Carbon::today()->toDateString();
                    $tomorrow = Carbon::tomorrow()->toDateString();

                    $invoicesCollection = $invoicesCollection->whereBetween('invoices.period_start', [
                        empty($from)
                            ? Carbon::now()->subDays(14)->toDateString() : date($from),
                        empty($to)
                            ? $tomorrow : Carbon::parse($to)->addDay()->toDate()->format('Y-m-d')
                    ]);

                    $invoicesCollection = $invoicesCollection->whereBetween('invoices.period_end', [
                        empty($from)
                            ? Carbon::now()->subDays(14)->toDateString() : date($from),
                        empty($to)
                            ? $tomorrow : Carbon::parse($to)->addDay()->toDate()->format('Y-m-d')
                    ]);

                    unset($filters->filterArray[$key]);
                }

                if ($filter->columnName === 'table_search') {
                    $term = $filter->value ?? null;
                    unset($filters->filterArray[$key]);
                }
            }

            $invoicesCollection = $invoicesCollection->where(function ($query) use ($filters) {
                foreach ($filters->filterArray as $filter) {
                    if ($filter->columnName !== 'ordering' && !empty($filter->value)) {
                        $query->where($filter->columnName, $filter->value);
                    }
                }
            });
        }

        if ($customerIds)
            $invoicesCollection = $invoicesCollection->whereIn('invoices.customer_id', $customerIds);

        return $invoicesCollection;
    }

    public function searchInvoiceLineItems($term, Invoice $invoice)
    {
        $invoiceLineItemsCollection = InvoiceLineItem::where('invoice_line_items.invoice_id', $invoice->id);

        $filters = json_decode($term);

        if ($filters->filterArray ?? false) {
            foreach ($filters->filterArray as $key => $filter) {
                if ($filter->columnName === 'table_search') {
                    $term = $filter->value ?: null;
                    unset($filters->filterArray[$key]);
                }
            }

            $invoiceLineItemsCollection = $invoiceLineItemsCollection->where(function ($query) use ($filters) {
                foreach ($filters->filterArray as $filter) {
                    if ($filter->columnName !== 'ordering' && !empty($filter->value)) {
                        $query->where($filter->columnName, $filter->value);
                    }
                }
            });
        }

        if ($term) {
            $term = '%' . $term . '%';

            $invoiceLineItemsCollection
                ->where(function ($query) use ($term) {
                    $query
                    ->whereHas('shipment.contactInformation', function ($query) use ($term) {
                        $query->where('name', 'like', $term);
                        $query->orWhere('address', 'like', $term);
                    })
                    ->orWhere('orders.number', 'like', $term)
                    ->orWhere('invoice_line_items.description', 'like', $term)
                    ->orWhere('invoice_line_items.total_charge', 'like', $term)
                    ->orWhere('shipments.tracking_code', 'like', $term)
                    ->orWhere('shipments.shipped_at', 'like', $term)
                    ->orWhere('billing_rates.name', 'like', $term)
                    ->orWhere('countries.title', 'like', $term);
                });
        }

        return $invoiceLineItemsCollection;
    }

    /**
     * @param $customer
     * @param array $input
     * @return Invoice
     * @throws BillingException
     */
    private function generateInvoice($customer, array $input): Invoice
    {
        if ($customer->rateCards->count() < 1) {
            throw new BillingException(__('The customer has no rate cards assigned.'));
        }

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'period_start' => $input['start_date'],
            'period_end' => $input['end_date'],
            'due_date' => null
        ]);

        foreach ($customer->rateCards as $rateCard) {
            $invoice->rateCards()->attach($rateCard->id);
            // $invoice->rateCards()->attach($rateCard->id, ['priority' => $rateCard->pivot->priority]);
        }

        return $invoice;
    }

    /**
     * @param User|null $user
     * @param Invoice $newInvoice
     * @return void
     */
    public function sendInvoiceSuccessEmail(?User $user, Invoice $newInvoice): void
    {
        if (!empty($user)) {
            $customer = $newInvoice->customer;
            $emailData = [
                'client_name' => $customer->contactInformation->name,
                'start_date' => $newInvoice->period_start->format('Y-m-d'),
                'end_date' => $newInvoice->period_end->format('Y-m-d'),
                'link' => route(
                    'billings.customer_invoice_line_items',
                    ['customer' => $customer->id, 'invoice' => $newInvoice->id]
                ),
            ];

            try {
                $this->mailComponent->sendEmail(
                    $user->email,
                    InvoiceCalculationDone::class,
                    $emailData
                );
            } catch (Exception $exception) {
                Log::error($exception->getMessage());
            }
        }
    }

    /**
     * @description final actions for batch
     * @param BulkInvoiceBatch $batchBill
     * @param User|null $user
     * @return void
     */
    public function bulkInvoiceBatchFinishTask(BulkInvoiceBatch $batchBill, ?User $user = null): void
    {
        $anyFailure = false;
        $batchBill->refresh(); //ensures batch invoice has the correct data
        if ($batchBill->invoices()->get()->isEmpty()) {
            $anyFailure = true;
            Log::info(sprintf('Batch bill %s failure, not invoice attached.', $batchBill->id));
        } else {
            foreach ($batchBill->invoices()->get() as $invoice) {

                if ($invoice->getInvoiceStatus() != InvoiceStatus::DONE_STATUS) {
                    $anyFailure = true;
                    Log::info(sprintf('Invoice %s is not in done status', $invoice->id));
                    break;
                }
            }
        }
        Log::info(sprintf('Batch bill %s done calculating', $batchBill->id));
        $batchBill->setBulkInvoiceBatchStatus($anyFailure ? InvoiceStatus::FAILED_STATUS : InvoiceStatus::DONE_STATUS);

        // Any other task after is done
    }

    public function finalize(Invoice $invoice): void
    {
        if (!$invoice->customer->is3pl()) {
            $threePlId = $invoice->customer->parent_id;
        } else {
            $threePlId = $invoice->customer->id;
        }

        $lastInvoice = Invoice::whereHas('customer', static function ($query) use ($threePlId) {
            $query->where('parent_id', $threePlId);
        })
            ->where('id', '!=', $invoice->id)
            ->whereNotNull('is_finalized')
            ->orderBy('is_finalized', 'desc')
            ->first();

        if ($lastInvoice && $lastInvoice->invoice_number) {
            $invoice_number = $lastInvoice->invoice_number;
            $invoice_number++;
        } else {
            $invoice_number = 10000;
        }

        $invoice->is_finalized = Carbon::now()->toDateTime();
        $invoice->invoice_number = $invoice_number;
        $invoice->save();
    }
}
