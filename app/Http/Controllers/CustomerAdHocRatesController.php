<?php

namespace App\Http\Controllers;

use App\Components\InvoiceComponent;
use App\Http\Requests\Invoice\AdHocBillingRequest;
use App\Models\BillingRate;
use App\Models\BulkInvoiceBatch;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerAdHocRatesController extends Controller
{
    public function index(string $bulkInvoiceBatch, Customer $customer)
    {
        $rateCards = $customer->rateCards()->get(['rate_cards.id']);
        $billingRates = BillingRate::query()
            ->whereIn('rate_card_id', $rateCards->pluck('id'))
            ->where('type', BillingRate::AD_HOC)
            ->get()
            ->map(function (BillingRate $rate) {
               $unit = $rate->settings['unit'] ?? 'NO UNIT';
               $name = "Name: {$rate->name} - Description: {$rate->description} - Unit: {$unit} - Unit Rate: " . (empty($rate->settings['fee']) ? 'NO RATE' : $rate->settings['fee']);
               return ['id' => $rate->id, 'name' => $name];
            });

        return response()->json([
            'rates' => $billingRates
        ]);
    }

    public function store(AdHocBillingRequest $request, BulkInvoiceBatch $bulkInvoiceBatch, InvoiceComponent $component)
    {
        Validator::validate($request->all(), [
            'customers_ids' => 'required|array',
        ]);
        $customerIds = $request->input('customers_ids');

        DB::transaction(function () use ($customerIds, $bulkInvoiceBatch, $request, $component) {
            foreach ($customerIds as $customer_id) {
                if ($customer_id === "all") {
                    continue;
                }

                $invoice = $bulkInvoiceBatch->invoices()->where('customer_id', $customer_id)->first();
                $component->adHoc($request, $invoice);
            }
        });

        return redirect()->back()->withStatus(__('Ad Hoc successfully added.'));
    }
}
