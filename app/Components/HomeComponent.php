<?php

namespace App\Components;

use App\Models\Customer;
use App\Models\Order;
use App\Models\PickingBatch;
use App\Models\Task;
use App\Models\Image;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Webpatser\Countries\Countries;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class HomeComponent extends BaseComponent
{
    public function statistics(Request $request) {
        $customerIds = Auth()->user()->customerIds(true, true);

        if (in_array($request->customer_id, $customerIds)) {
            $customerIds = Customer::withClients($request->customer_id)->pluck('id')->toArray();
        } else {
            $customerIds = [];
        }

        return [
            'ordersReadyToShip' => $this->ordersReadyToShip($customerIds),
            'itemsReadyToShip' => $this->itemsReadyToPick($customerIds),
            'ordersDueToday' => $this->ordersDueToday($customerIds),
            'itemsShippedToday' => $this->ordersShippedToday($customerIds),
            'batchesLeft' => $this->batchesLeft($customerIds),
        ];
    }

    public function ordersReadyToShip($customerIds)
    {
        $orders = Order::whereIntegerInRaw('customer_id', $customerIds)->where('ready_to_ship', '1');

        return $orders->count();
    }

    public function itemsReadyToPick($customerIds)
    {
        $orders = Order::whereIntegerInRaw('customer_id', $customerIds)
            ->where('ready_to_pick', '1')
            ->withSum('orderItems', 'quantity')
            ->get();

        return $orders->sum('order_items_sum_quantity');
    }

    public function ordersDueToday($customerIds)
    {
        $orders = Order::whereIntegerInRaw('customer_id', $customerIds)
            ->whereNull('fulfilled_at')
            ->whereNull('cancelled_at')
            ->whereNotNull('ship_before');

        return $orders->count();
    }

    public function ordersShippedToday($customerIds)
    {
        $userDate = Carbon::now(user_timezone());

        $startDate = Carbon::parseInUserTimezone($userDate)
            ->startOfDay()
            ->toServerTime();

        $endDate = Carbon::parseInUserTimezone($userDate)
            ->endOfDay()
            ->toServerTime();

        $orders = Order::whereHas('shipments', function(Builder $query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        })->whereIntegerInRaw('customer_id', $customerIds);

        return $orders->count();
    }

    public function batchesLeft($customerIds)
    {
        $user = auth()->user();

        $data = [
            'sib' => 0,
            'mib' => 0,
            'so' => 0,
        ];

        $taskIds = Task::where('user_id', $user->id)->whereIntegerInRaw('customer_id', $customerIds)->where('taskable_type', PickingBatch::class)->where('completed_at', null)->pluck('taskable_id')->toArray();;
        $pickingBatches = PickingBatch::whereIntegerInRaw('id', $taskIds)->get();

        foreach ($pickingBatches as $pickingBatch) {
            $data[$pickingBatch->type]++;
        }

        return $data;
    }

    public function getCountries()
    {
        return Countries::pluck('name', 'id')->all();
    }

    public function deleteImage(Request $request, Image $image)
    {
        if (Storage::exists($image->filename)) {
            Storage::delete($image->filename);
        }

        $image->delete();

        return response()->json(['success' => true]);
    }

    public function pageTitle(): string
    {
        $routeName = request()->route()->getName();

        if (empty($routeName)) {
            return config('app.name');
        }

        $pageTitle = Arr::get(config('settings.page_title'), $routeName, config('app.name'));

        if ($routeName == 'order.edit') {
            $pageTitle .= request()->route('order')->number;
        } else if ($routeName == 'product.edit') {
            $pageTitle .= request()->route('product')->sku;
        } else if ($routeName == 'purchase_orders.edit') {
            $pageTitle .= request()->route('purchase_order')->number;
        } else if ($routeName == 'purchase_order.receive') {
            $pageTitle .= request()->route('purchaseOrder')->number;
        } else if ($routeName == 'report.view') {
            $pageTitle = Str::title(str_replace('_',' ', request()->route('reportId'))) . $pageTitle;
        } else if ($routeName == 'tote.edit') {
            $pageTitle .= request()->route('tote')->name;
        } else if ($routeName == 'location_type.edit') {
            $pageTitle .= request()->route('location_type')->name;
        } else if ($routeName == 'billings.customer_invoices') {
            $pageTitle = request()->route('customer')->contactInformation->name . $pageTitle;
        } else if ($routeName == 'packing.single_order_shipping') {
            $pageTitle .= request()->route('order')->number;
        } else if ($routeName == 'bulk_shipping.shipping') {
            $pageTitle .= request()->route('bulkShipBatch')->id;
        }

        return $pageTitle;
    }
}
