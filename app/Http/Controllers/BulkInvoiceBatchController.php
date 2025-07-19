<?php

namespace App\Http\Controllers;

use App\Components\InvoiceComponent;
use App\Components\UserComponent;
use App\Http\Requests\Invoice\BatchStoreRequest;
use App\Http\Resources\BulkInvoiceBatchResource;
use App\Http\Resources\CustomerInvoiceLineItemsTableResource;
use App\Models\BillingRate;
use App\Models\BulkInvoiceBatch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\RateCard;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BulkInvoiceBatchController extends Controller
{
    public function __construct(private readonly InvoiceComponent $invoiceComponent)
    {
    }

    public function dataTable(Request $request)
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');

        $sortColumnName = 'bulk_invoice_batches.id';
        $sortDirection = 'desc';

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];

            if ($sortColumnName === 'number') {
                $sortColumnName = 'bulk_invoice_batches.id';
            }
        }

        $batchBillCollection = BulkInvoiceBatch::query()
            ->select([
                'bulk_invoice_batches.id',
                // 'bulk_invoice_batches.status' should be capitalized
                'bulk_invoice_batches.status',
                'bulk_invoice_batches.updated_at',
                DB::raw('SUM(invoices.amount) as amount'),
                DB::raw('MIN(contact_informations.name) as first_customer_name'), // Assuming contact_informations.name is the customer name
                DB::raw('COUNT(DISTINCT customers.id) as total_customers'),
                DB::raw("
                    CASE
                        WHEN COUNT(DISTINCT customers.id) > 1
                            THEN CONCAT(MIN(contact_informations.name), ' + ', COUNT(DISTINCT customers.id) - 1)
                        ELSE MIN(contact_informations.name)
                    END as name
                "),
                DB::raw("
                    CONCAT(DATE_FORMAT(MIN(bulk_invoice_batches.period_start), '%m-%d-%Y'), ' - ', DATE_FORMAT(MAX(bulk_invoice_batches.period_end), '%m-%d-%Y')) as period
                "),
                DB::raw('COUNT(invoices.id) as total_invoices'),
                // If total_invoices = 1, then we select the invoice id, null otherwise
                DB::raw('
                    CASE
                        WHEN COUNT(invoices.id) = 1
                            THEN MIN(invoices.id)
                        ELSE NULL
                    END as invoice_id
                '),
                'customers.id as customer_id',
                DB::raw("
                    GROUP_CONCAT(contact_informations.name ORDER BY contact_informations.name SEPARATOR '&#013') as all_customers
                ")
            ])
            ->join('bulk_invoice_batch_invoice', 'bulk_invoice_batches.id', '=', 'bulk_invoice_batch_invoice.bulk_invoice_batch_id')
            ->join('invoices', 'bulk_invoice_batch_invoice.invoice_id', '=', 'invoices.id')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->join('contact_informations', function (JoinClause $join) {
                $join->on('contact_informations.object_id', '=', 'customers.id')
                    ->where('contact_informations.object_type', Customer::class);
            })
            ->groupBy('bulk_invoice_batches.id')
            ->orderBy($sortColumnName, $sortDirection);

        $term = $request->get('search')['value'];
        if ($term) {
            $term = '%' . $term . '%';

            $batchBillCollection
                ->where('contact_informations.name', 'like', $term);
        }

        $batchBills = $batchBillCollection
            ->skip($request->get('start'))->limit($request->get('length'))
            ->get()
            ->map(function (BulkInvoiceBatch $batch) {
                $batch->other_customers = Str::after($batch->all_customers, $batch->first_customer_name . '&#013');

                return $batch;
            });
        $visibleFields = app('editColumn')->getVisibleFields('invoices');

        return response()->json([
            'data' => BulkInvoiceBatchResource::collection($batchBills),
            'visibleFields' => $visibleFields,
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX
        ]);
    }

    public function store(BatchStoreRequest $request, UserComponent $userComponent)
    {
        $input = $request->all();
        $current_customer = $userComponent->get3plCustomer();

        $customer_ids = $input['customer_ids'];
        if (in_array('all', $customer_ids)) {
            // All 3PL children that have rate cards
            $customer_ids = $current_customer->children()
                ->select('id')
                ->whereHas('rateCards')
                ->get()
                ->pluck('id')
                ->toArray();

            $request->merge(['customer_ids' => $customer_ids]);
        }

        if ($input['type'] === 'individual') {
            foreach ($customer_ids as $customer_id) {
                $this->invoiceComponent->batchStore(new BatchStoreRequest([
                    ...$input,
                    'customer_ids' => [$customer_id],
                ]), $current_customer, auth()->user());
            }
        } else {
            $this->invoiceComponent->batchStore($request, $current_customer, auth()->user());
        }

        return redirect()->route('billings.invoices')->withStatus('Your invoices are being generated. You\'ll receive an email once the process has completed.');
    }

    public function edit(BulkInvoiceBatch $bulkInvoiceBatch)
    {
        $bulkInvoiceBatch->load('bulkInvoiceBatchInvoices.invoice.customer.contactInformation');
        return view('billings.invoices.edit', [
            'datatableOrder' => app()->editColumn->getDatatableOrder('batch-invoice-line-items'),
            'batch' => $bulkInvoiceBatch,
            'invoice' => Invoice::first(),
            'customer' => Customer::first(),
            'data' => new Collection([
                'batchCustomers' => $bulkInvoiceBatch->bulkInvoiceBatchInvoices->pluck('invoice.customer.contactInformation.name', 'invoice.customer.id'),
                'rateCards' => RateCard::query()->get(['id', 'name'])->pluck('name', 'id'),
            ]),
        ]);
    }

    public function destroy(BulkInvoiceBatch $bulkInvoiceBatch)
    {
        foreach ($bulkInvoiceBatch->bulkInvoiceBatchInvoices as $invoice) {
            $this->invoiceComponent->destroy($invoice->invoice);
        }

        $bulkInvoiceBatch->delete();

        return redirect()->route('billings.invoices')->withStatus('The bulk invoice batch has been deleted.');
    }

    public function finalize(BulkInvoiceBatch $bulkInvoiceBatch)
    {
        foreach ($bulkInvoiceBatch->bulkInvoiceBatchInvoices as $invoice) {
            $this->invoiceComponent->finalize($invoice->invoice);
        }

        return redirect()->route('billings.invoices')->withStatus('All invoices from this bulk invoice batch have been finalized.');
    }

    public function recalculate(BulkInvoiceBatch $bulkInvoiceBatch, UserComponent $userComponent)
    {
        $current_customer = $userComponent->get3plCustomer();
        $invoices = $bulkInvoiceBatch->bulkInvoiceBatchInvoices()
            ->with(['invoice' => function (BelongsTo $query) {
                $query->select('id', 'customer_id');
            }])
            ->get();

        $request = new BatchStoreRequest([
            'start_date' => $bulkInvoiceBatch->period_start,
            'end_date' => $bulkInvoiceBatch->period_end,
            'customer_ids' => $invoices->pluck('invoice.customer_id')->toArray(),
        ]);

        $this->invoiceComponent->batchStore($request, $current_customer, auth()->user());

        return redirect()->route('billings.invoices')->withStatus('All invoices from this bulk invoice batch have been recalculated.');
    }

    public function customersByRateCard(BulkInvoiceBatch $bulkInvoiceBatch, RateCard $rateCard)
    {
        $customersAttachedToRateCard = $rateCard->customers()
            ->whereIn('customers.id', $bulkInvoiceBatch->bulkInvoiceBatchInvoices->pluck('invoice.customer_id'))
            ->with('contactInformation')
            ->get()
            ->map(function (Customer $customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->contactInformation->name,
                ];
            });

        return response()->json([
            'data' => [
                'customers' => $customersAttachedToRateCard,
                'charges' => $rateCard->billingRates()
                    ->where('type', BillingRate::AD_HOC)
                    ->get()
                    ->map(function (BillingRate $rate) {
                        $unit = $rate->settings['unit'] ?? 'NO UNIT';
                        $name = "Name: {$rate->name} - Description: {$rate->description} - Unit: {$unit} - Unit Rate: " . (empty($rate->settings['fee']) ? 'NO RATE' : $rate->settings['fee']);
                        return ['id' => $rate->id, 'name' => $name];
                    }),
            ]
        ]);
    }
}
