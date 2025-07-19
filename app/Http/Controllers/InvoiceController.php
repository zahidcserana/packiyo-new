<?php

namespace App\Http\Controllers;

use App\Components\InvoiceComponent;
use App\Exceptions\InvoiceFinalizedException;
use App\Http\Requests\Invoice\AdHocBillingRequest;
use App\Http\Requests\Invoice\DeleteInvoiceLineItemRequest;
use App\Http\Requests\Invoice\UpdateAdHocBillingRequest;
use App\Http\Requests\Invoice\RecalculateBatchRequest;
use App\Http\Requests\Invoice\StoreBatchRequest;
use App\Http\Requests\Invoice\StoreRequest;
use App\Jobs\Billing\RecalculateInvoiceJob;
use App\Models\BulkInvoiceBatch;
use App\Models\Invoice;
use App\Models\BillingRate;
use App\Models\InvoiceLineItem;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\{Facades\Cache, Facades\Log, Facades\View};
use App\Exceptions\BillingException;

class InvoiceController extends Controller
{
    /**
     * @return RedirectResponse
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('billings.invoices');
    }

    /**
     * @param StoreRequest $request
     * @return mixed
     */
    public function store(StoreRequest $request)
    {
        try {
            $user = auth()->user();
            return app('invoice')->store($request, $user);
        } catch (BillingException $e) {
            return redirect()->back()->withErrors(__("Could not calculate invoice. :message", ['message' => $e->getMessage()]));
        }
    }

    /**
     * @param StoreBatchRequest $request
     * @return RedirectResponse
     */
    public function batchStore(StoreBatchRequest $request): RedirectResponse
    {
        $input = $request->validated();
        $customersSelected = json_decode($input['store_customers_selected']);
        $validatedStoreRequests = [];

        foreach ($customersSelected as $customer) {
            $record['customer_id'] = $customer;
            $record['start_date'] = $input['start_date'];
            $record['end_date'] = $input['end_date'];

            $validatedStoreRequests[] = StoreRequest::make($record);
        }

        foreach ($validatedStoreRequests as $validatedStoreRequest) {
            $this->store($validatedStoreRequest);
        }

        return redirect()->back();
    }

    /**
     * @param RecalculateBatchRequest $request
     * @return RedirectResponse
     */
    public function batchRecalculate(RecalculateBatchRequest $request): RedirectResponse
    {
        $input = $request->validated();

        $dateRange = $input['dates_between'];
        $customers = json_decode($input['recalculate_customers_selected'], true);

        $dates = explode(" ", $dateRange);
        $from = Arr::get($dates, '0', '');
        $to = Arr::get($dates, '2', '');

        foreach ($customers as $customer) {
            $customer = Customer::find($customer);

            if ($customer) {
                $invoices = $customer->invoices()->where(function ($query) use ($from, $to) {
                    return $query->whereBetween('period_end', [$from, $to])
                        ->whereBetween('period_start', [$from, $to])
                        ->whereNull('is_finalized');
                })->get();

                foreach ($invoices as $invoice) {
                    $this->recalculate($invoice);
                }
            }
        }

        return redirect()->back();
    }

    /**
     * @param AdHocBillingRequest $request
     * @param Invoice $invoice
     * @return mixed
     */
    public function adHoc(AdHocBillingRequest $request, Invoice $invoice)
    {
        try {
            app('invoice')->adHoc($request, $invoice);
        } catch (BillingException $e) {
            return redirect()->back()->withErrors(__("Could not create rate. :message", ['message' => $e->getMessage()]));
        }

        return redirect()->back()->withStatus(__('Ad Hoc successfully added.'));
    }

    public function updateAdHoc(UpdateAdHocBillingRequest $request, Invoice $invoice, InvoiceLineItem $invoiceLineItem)
    {
        try {
            app('invoice')->updateAdHoc($request, $invoice, $invoiceLineItem);
        } catch (BillingException $e) {
            return redirect()->back()->withErrors(__("Could not create rate. :message", ['message' => $e->getMessage()]));
        }

        return redirect()->back()->withStatus(__('Ad Hoc successfully updated.'));
    }

    public function deleteAdHoc (Invoice $invoice, InvoiceLineItem $invoiceLineItem)
    {
        app('invoice')->deleteAdHoc($invoiceLineItem);

        return redirect()->back()->withStatus(__('Ad Hoc successfully deleted.'));
    }

    public function getEditInvoiceLineItemForm(Invoice $invoice, InvoiceLineItem $invoiceLineItem)
    {
        $rateCards = $invoice
            ->customer
            ->rateCards();

        $adHocs = BillingRate::where('type', 'ad_hoc')
            ->whereIn('rate_card_id', $rateCards->pluck('rate_cards.id')->toArray())
            ->get();

        return View::make('shared.modals.components.invoice_line_item.edit', compact('invoice', 'invoiceLineItem', 'adHocs'));
    }

    /**
     * @param Invoice $invoice
     * @return RedirectResponse
     */
    public function recalculate(Invoice $invoice)
    {
        if ($invoice->is_finalized) {
            return redirect()
                ->route('billings.customer_invoices',[
                    'customer' => $invoice->customer_id
                ])
                ->withErrors(__('Invoice finalized.'));
        }

        $invoice->calculated_at = null;
        $invoice->save();
        $user = auth()->user();

        try {
            app('invoice')->recalculate($invoice, $user);

        } catch (InvoiceFinalizedException $e) {
            Log::warning(sprintf("Invoice %s, has been finalized, recalculation is not allowed.", $invoice->id));
            return redirect()
                ->route('billings.customer_invoices', [
                    'customer' => $invoice->customer
                ])
                ->withErrors(__('Invoice finalized.'));
        }

        return redirect()
            ->route('billings.customer_invoices', [
                'customer' => $invoice->customer_id
            ])
            ->withStatus(__('Recalculating invoice.'));
    }

    /**
     * @param Invoice $invoice
     * @return RedirectResponse
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        if ($invoice->is_finalized) {
            return redirect()
                ->route('billings.customer_invoices',[
                    'customer' => $invoice->customer
                ])
                ->withErrors(__('Invoice finalized.'));
        }

        app('invoice')->destroy($invoice);

        return redirect()
            ->route('billings.customer_invoices',[
                'customer' => $invoice->customer
            ])
            ->withStatus(__('Invoice successfully deleted.'));
    }

    /**
     * @param Invoice $invoice
     * @return RedirectResponse
     */
    public function finalize(Invoice $invoice, InvoiceComponent $component): RedirectResponse
    {
        $component->finalize($invoice);

        return redirect()->back()->with('status', 'Invoice finalized.');
    }

    /**
     * @param Invoice $invoice
     * @return mixed
     */
    public function generateCsv(Invoice $invoice)
    {
        return app('invoice')->generateCsv($invoice);
    }

    /**
     * @param Invoice $invoice
     * @return mixed
     */
    public function downloadGeneratedCsv(Invoice $invoice)
    {
        return app('invoice')->downloadGeneratedCsv($invoice);
    }

    /**
     * @param Invoice $invoice
     * @return mixed
     */
    public function exportToCsv(Invoice $invoice)
    {
        return app('invoiceExport')->exportToCsv($invoice);
    }

    public function exportBatchInvoiceToCsv(BulkInvoiceBatch $bulkInvoiceBatch)
    {
        return app('invoiceExport')->exportBatchInvoiceToCsv($bulkInvoiceBatch);
    }

    /**
     * @param Invoice $invoice
     * @return mixed
     * @throws AuthorizationException
     */
    public function exportInvoiceSummary(Invoice $invoice)
    {
        return app('invoiceExport')->exportToCsv($invoice);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function exportInvoiceLines(Request $request)
    {
        $input = $request->input();
        $dates = explode(" ", $input["date_range"]);
        $from = Arr::get($dates, '0', '');
        $to = Arr::get($dates, '2', '');
        $invoices = $this->getInvoicesForPeriod($from, $to);

        return app('invoice')->exportInvoiceLinesToCsv($to, $invoices);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function exportInvoiceHeader(Request $request)
    {
        $input = $request->input();
        $dates = explode(" ", $input["date_range"]);
        $from = Arr::get($dates, '0', '');
        $to = Arr::get($dates, '2', '');

        $invoices = $this->getInvoicesForPeriod($from, $to);

        return app('invoice')->exportInvoiceHeaderToCsv($to, $invoices);
    }

    /**
     * @param $period_start
     * @param $period_end
     * @param $customer_id
     * @return mixed
     */
    private function getInvoicesForPeriod($period_start, $period_end, $customer_id = null)
    {
        $invoices = Invoice::whereBetween('period_start', [$period_start, $period_end])
            ->whereBetween('period_end', [$period_start, $period_end]);

        if ($customer_id) {
            $invoices = $invoices->where('customer_id', $customer_id);
        }

        return $invoices->get();
    }

    /**
     * @param Invoice $invoice
     * @return Response
     */
    public function exportInvoicePDF(Invoice $invoice): Response
    {
        $image = file_get_contents(login_logo());
        $type = pathinfo(login_logo(), PATHINFO_EXTENSION);
        $base64 = "data:image/". $type .";base64,".base64_encode($image);

        $tax = $invoice->customer->vat;
        $pdf = PDF::loadView('pdf.' . $invoice->customer->threePl->export_template_name, ['invoice' => $invoice, 'tax' => $tax, 'image' => $base64]);

        $fileName = $invoice->customer->contactInformation->name . ' ' . localized_date($invoice->period_start) . ' - ' . localized_date($invoice->period_end) . '-invoice.pdf';

        return $pdf->download($fileName);
    }
}
