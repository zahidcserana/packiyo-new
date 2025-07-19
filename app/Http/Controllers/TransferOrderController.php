<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransferOrderTableResource;
use App\Models\Order;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Application;
use Illuminate\Http\{JsonResponse, Request};

class TransferOrderController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Order::class);
    }

    public function index()
    {
        return view('transfer_orders.index', [
            'datatableOrder' => app('editColumn')->getDatatableOrder('transfer-orders')
        ]);
    }

    public function dataTable(Request $request): JsonResponse
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'ordered_at';
        $sortDirection = 'desc';

        $term = $request->get('search')['value'];
        $status = $request->get('filter_form')['status'];

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        $transferOrderCollection = Order::with([
            'orderItems'
        ])
            ->whereIn('customer_id', $customerIds)
            ->whereHas('purchaseOrder')
            ->when((!empty($status) && $status !== '0'), static function (Builder $query) use ($status) {
                if ($status == 'created') {
                    $query->whereNull('fulfilled_at')
                        ->whereNull('cancelled_at');
                } else if ($status == 'shipped') {
                    $query->whereNotNull('fulfilled_at')
                        ->whereNull('cancelled_at');
                } else if ($status == 'received') {
                    $query->whereHas('purchaseOrder', function (Builder $query) use ($status) {
                        $query->whereNotNull('received_at');
                    });
                } else if ($status == 'closed') {
                    $query->whereHas('purchaseOrder', function (Builder $query) use ($status) {
                        $query->whereNotNull('closed_at');
                    });
                }
            })
            ->orderBy(trim($sortColumnName), $sortDirection);

        if ($term) {
            $transferOrderCollection = app('order')->searchQuery($term, $transferOrderCollection);
        }

        $start = $request->get('start');
        $length = $request->get('length');

        if ($length === -1) {
            $length = 10;
        }

        if ($length) {
            $transferOrderCollection = $transferOrderCollection->skip($start)->limit($length);
        }

        $transferOrders = $transferOrderCollection->get();
        $visibleFields = app('editColumn')->getVisibleFields('transfer-orders');

        $transferOrderCollection = TransferOrderTableResource::collection($transferOrders);

        return response()->json([
            'data' => $transferOrderCollection,
            'visibleFields' => $visibleFields,
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX
        ]);
    }

    /**
     * @param PurchaseOrder $purchaseOrder
     * @return mixed
     */
    public function close(PurchaseOrder $purchaseOrder)
    {
        app('purchaseOrder')->closePurchaseOrder($purchaseOrder);

        return redirect()->route('transfer_orders.index')->withStatus(__('Transfer order successfully closed'));
    }
}
