<?php

namespace App\Http\Controllers;

use App\Components\InvoiceComponent;
use App\Components\UserComponent;
use App\Http\Resources\BillingCustomerTableResource;
use App\Http\Resources\InvoicesTableResource;
use App\Http\Resources\CustomerInvoicesTableResource;
use App\Http\Resources\CustomerInvoiceLineItemsTableResource;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\BillingRate;
use App\Models\Customer;
use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class BillingController extends Controller
{
    public function index()
    {
        return redirect()->route('billings.customers');
    }

    public function customers()
    {
        return view('billings.customers.index', [
            'datatableOrder' => app()->editColumn->getDatatableOrder('billing_customers'),
        ]);
    }

    public function customerInvoicesEdit(Customer $customer)
    {
        $latestBill = Invoice::where('customer_id', $customer->id)
            ->orderBy('period_end', 'desc')
            ->first();

        $lastestLastDate = $latestBill
            ? Carbon::parse($latestBill['period_end'])->format('Y-m-d')
            : Carbon::now()->subYear();

        return view('billings.customers.invoices', [
            'customer' => $customer,
            'lastInvoiceEndDate' => $lastestLastDate,
            'datatableOrder' => app()->editColumn->getDatatableOrder('customer-invoices'),
        ]);
    }

    public function customerInvoicesDataTable(Request $request, Customer $customer)
    {
        $term = $request->input('search.value');

        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'invoices.id';
        $sortDirection = 'desc';

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $invoicesCollection = $customer->invoices()
            ->orderBy($sortColumnName, $sortDirection);

        if ($term) {
            $totalInvoicesCount = $customer->invoices()->count();
            $invoicesCount = $invoicesCollection->count();
        } else {
            $totalInvoicesCount = $invoicesCollection->count();
            $invoicesCount = $totalInvoicesCount;
        }

        $invoices = $invoicesCollection->skip($request->get('start'))->limit($request->get('length'))->get();
        $invoicesCollection = CustomerInvoicesTableResource::collection($invoices);
        $visibleFields = app('editColumn')->getVisibleFields('customer-invoices');

        return response()->json([
            'data' => $invoicesCollection,
            'recordsTotal' => $totalInvoicesCount,
            'recordsFiltered' => $invoicesCount,
            'visibleFields' => $visibleFields
        ]);
    }

    public function customersDataTable(Request $request): JsonResponse
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'customers.id';
        $sortDirection = 'desc';

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $customerCollection = Customer::join('contact_informations', 'customers.id', '=', 'contact_informations.object_id')
            ->where('contact_informations.object_type', Customer::class)
            ->whereIn('customers.id', app('user')->getSelectedCustomers()->pluck('id')->toArray())
            ->whereNotNull('parent_id')
            ->select('customers.*')
            ->groupBy('customers.id')
            ->orderBy($sortColumnName, $sortDirection);

        $term = $request->get('search')['value'];

        if ($term) {
            // TODO: sanitize term
            $term = $term . '%';

            $customerCollection
                ->whereHas('contactInformation', function($query) use ($term) {
                    $query->where('name', 'like', $term)
                        ->orWhere('company_name', 'like', $term)
                        ->orWhere('address', 'like', $term)
                        ->orWhere('address2', 'like', $term)
                        ->orWhere('zip', 'like', $term)
                        ->orWhere('city', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
        }

        $customers = $customerCollection->skip($request->get('start'))->limit($request->get('length'))->get();
        $visibleFields = app('editColumn')->getVisibleFields('billing-customers');

        return response()->json([
            'data' => BillingCustomerTableResource::collection($customers),
            'visibleFields' => $visibleFields,
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX,
        ]);

    }

    public function customerInvoiceLineItems(Customer $customer, Invoice $invoice)
    {
        $rateCards = $invoice
            ->customer
            ->rateCards();

        return view('billings.customers.invoiceLineItems', [
            'datatableOrder' => app()->editColumn->getDatatableOrder('invoice-line-items'),
            'invoice' => $invoice,
            'customer' => $customer,
            'adHocs' => BillingRate::where('type', 'ad_hoc')
                ->whereIn('rate_card_id', $rateCards->pluck('rate_cards.id')->toArray())
                ->get()
        ]);
    }

    public function customerInvoiceLineItemsDataTable(Request $request, Customer $customer, Invoice $invoice)
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

        $invoiceLineItemsCollection = app()->invoice->searchInvoiceLineItems($term, $invoice)
            ->leftJoin('billing_rates', 'invoice_line_items.billing_rate_id', '=', 'billing_rates.id')
            ->leftJoin('shipments', 'invoice_line_items.shipment_id', '=', 'shipments.id')
            ->leftJoin('orders', 'orders.id', '=', 'shipments.order_id')
            ->leftJoin('contact_informations AS shipment_contact_information', function ($join) {
                $join->on('shipments.id', '=', 'shipment_contact_information.object_id')
                    ->where('shipment_contact_information.object_type', Shipment::class);
            })
            ->leftJoin('countries', 'shipment_contact_information.country_id', '=', 'countries.id')
            ->select('*', 'billing_rates.name', 'invoice_line_items.*')
            ->orderBy($sortColumnName, $sortDirection);

        if ($term) {
            $totalInvoiceLineItemsCount = InvoiceLineItem::count();
            $invoiceLineItemsCount = $invoiceLineItemsCollection->count();
        } else {
            $totalInvoiceLineItemsCount = $invoiceLineItemsCollection->count();
            $invoiceLineItemsCount = $totalInvoiceLineItemsCount;
        }

        $invoiceLineItems = $invoiceLineItemsCollection->skip($request->get('start'))->limit($request->get('length'))->get();
        $invoiceLineItemsCollection = CustomerInvoiceLineItemsTableResource::collection($invoiceLineItems);
        $visibleFields = app('editColumn')->getVisibleFields('invoice-line-items');

        return response()->json([
            'data' => $invoiceLineItemsCollection,
            'currency' => customer_settings($customer->id, 'currency') ?? 'USD',
            'visibleFields' => $visibleFields,
            'recordsTotal' => $totalInvoiceLineItemsCount,
            'recordsFiltered' => $invoiceLineItemsCount
        ]);
    }

    public function invoices(UserComponent $user_component): Factory|View|\Illuminate\Contracts\Foundation\Application
    {
        $current_3pl = $user_component->get3plCustomer();
        $children = $current_3pl->children()
            ->whereHas('rateCards')
            ->with('contactInformation')->get()->pluck('contactInformation.name', 'id')->toArray();

        return view('billings.invoices.index', [
            'datatableOrder' => app()->editColumn->getDatatableOrder('invoices'),
            'customers' => $children
        ]);
    }

    public function invoicesDataTable(Request $request)
    {
        $sortDirection = Arr::get($request->input('order'), '0.dir', 'desc');
        $tableColumnName = Arr::get(
            $request->input('columns'),
            $request->input('order')[0]['column'] . '.' . 'name',
            'period_end'
        );

        $term = $request->input('search.value');
        $invoicesCollection = Invoice::query()->orderBy($tableColumnName, $sortDirection)->whereNotNull('is_finalized');

        if (!empty($term) && array_key_exists('value', json_decode($term, true)['filterArray'][0])) {
            $dateRange = json_decode($term, true)['filterArray'][0]['value'];
            $dates = explode(" ", $dateRange);
            $from = Arr::get($dates, '0', '');
            $to = Arr::get($dates, '2', '');

            $invoicesCollection = $invoicesCollection->where(function ($query) use ($from, $to) {
                    return $query->whereBetween('period_end', [$from, $to])
                        ->whereBetween('period_start', [$from, $to]);
                });
        }

        $invoicesCollection = $invoicesCollection->whereIn('customer_id', auth()->user()->customerIds());

        if ($term) {
            $totalInvoicesCount = Invoice::count();
            $invoicesCount = $invoicesCollection->count();
        } else {
            $totalInvoicesCount = $invoicesCollection->count();
            $invoicesCount = $totalInvoicesCount;
        }

        $invoices = $invoicesCollection->skip($request->get('start'))->limit($request->get('length'))->get();
        $invoicesCollection = InvoicesTableResource::collection($invoices);

        return response()->json([
            'data' => $invoicesCollection,
            'recordsTotal' => $totalInvoicesCount,
            'recordsFiltered' => $invoicesCount
        ]);
    }

    public function rateCards()
    {
        return view('billings.rate_cards.index', [
            'datatableOrder' => app()->editColumn->getDatatableOrder('rate_cards'),
        ]);
    }

    public function exports()
    {
        return view('billings.exports.index');
    }
}
