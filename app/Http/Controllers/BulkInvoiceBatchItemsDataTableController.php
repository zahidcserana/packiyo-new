<?php

namespace App\Http\Controllers;

use App\Components\InvoiceComponent;
use App\Components\UserComponent;
use App\Http\Requests\Invoice\BatchStoreRequest;
use App\Http\Resources\BulkInvoiceBatchResource;
use App\Http\Resources\CustomerInvoiceLineItemsTableResource;
use App\Models\BulkInvoiceBatch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BulkInvoiceBatchItemsDataTableController extends Controller
{
    public function __invoke(Request $request, BulkInvoiceBatch $bulkInvoiceBatch)
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'billing_rates.id';
        $sortDirection = 'desc';
        $term = $request->get('search')['value'];

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $baseQuery = InvoiceLineItem::query()
            ->select('*', 'billing_rates.name', 'invoice_line_items.*')
            ->with(['invoice.customer.contactInformation'])
            ->join('invoices', 'invoice_line_items.invoice_id', '=', 'invoices.id')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->leftJoin('billing_rates', 'invoice_line_items.billing_rate_id', '=', 'billing_rates.id')
            ->leftJoin('shipments', 'invoice_line_items.shipment_id', '=', 'shipments.id')
            ->leftJoin('orders', 'orders.id', '=', 'shipments.order_id')
            ->leftJoin('contact_informations AS shipment_contact_information', function ($join) {
                $join->on('shipments.id', '=', 'shipment_contact_information.object_id')
                    ->where('shipment_contact_information.object_type', Shipment::class);
            })
            ->leftJoin('countries', 'shipment_contact_information.country_id', '=', 'countries.id')
            ->whereIn('invoice_id', $bulkInvoiceBatch->bulkInvoiceBatchInvoices->pluck('invoice_id'));

        $this->filter($request, $baseQuery);
        $this->search($request, $baseQuery);

        $invoiceLineItemsCollection = $baseQuery
            ->orderBy($sortColumnName, $sortDirection);

        if ($term) {
            $totalInvoiceLineItemsCount = $baseQuery->count();
            $invoiceLineItemsCount = $invoiceLineItemsCollection->count();
        } else {
            $totalInvoiceLineItemsCount = $invoiceLineItemsCollection->count();
            $invoiceLineItemsCount = $totalInvoiceLineItemsCount;
        }

        return response()->json([
            'data' => CustomerInvoiceLineItemsTableResource::collection(
                resource: $invoiceLineItemsCollection->skip($request->get('start'))
                    ->limit($request->get('length'))
                    ->get()
            ),
            'visibleFields' => app('editColumn')->getVisibleFields('batch-invoices-line-items'),
            'recordsTotal' => $totalInvoiceLineItemsCount,
            'recordsFiltered' => $invoiceLineItemsCount
        ]);
    }

    public function filter(
        Request $request,
        Builder $baseQuery
    ): void {
        $filterForm = $request->get('filter_form');

        foreach ($filterForm as $column => $value) {
            if ($value == 0) {
                continue;
            }
            $baseQuery->where($column, $value);
        }
    }

    public function search(
        Request $request,
        Builder $baseQuery
    ): void {
        $term = $request->get('search')['value'];
        if (!$term) {
            return;
        }

        $term = "%$term%";
        $baseQuery
            ->where(fn (Builder $query) =>
                $query
                    ->whereHas('shipment.contactInformation', function ($query) use ($term) {
                        $query->where('name', 'like', $term);
                        $query->orWhere('address', 'like', $term);
                    })
                    ->orWhereHas('invoice.customer.contactInformation', function ($query) use ($term) {
                        $query->where('name', 'like', $term);
                    })
                    ->orWhere('orders.number', 'like', $term)
                    ->orWhere('invoice_line_items.description', 'like', $term)
                    ->orWhere('invoice_line_items.total_charge', 'like', $term)
                    ->orWhere('billing_rates.name', 'like', $term)
                    ->orWhere('countries.name', 'like', $term)
            );
    }
}
