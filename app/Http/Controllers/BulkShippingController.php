<?php

namespace App\Http\Controllers;

use App\Features\MultiWarehouse;
use App\Http\Resources\BulkShipBatchOrderTableResource;
use App\Http\Resources\BulkShippingTableResource;
use App\Models\BulkShipBatch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Laravel\Pennant\Feature;

class BulkShippingController extends Controller
{
    public function index(Request $request, $shipped = false, $inProgress = false)
    {
        if (!$request->ajax()) {
            return view('bulk_shipping.index', [
                'page' => 'bulk_shipping',
                'datatableOrder' => app('editColumn')->getDatatableOrder('bulk-shipping'),
            ]);
        }

        $customers = app('user')->getSelectedCustomers();
        $warehouseIds = [];

        if (Feature::for('instance')->active(MultiWarehouse::class)) {
            $warehouseIds = auth()->user()->warehouses->pluck('id')->toArray();
        }

        if (!$shipped && !$inProgress) {
            app('bulkShip')->syncBatchOrders($customers);
        }

        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'created_at';
        $sortDirection = 'desc';
        $filterInputs =  $request->get('filter_form');

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $bulkShipBatchQuery = BulkShipBatch::query()
            ->with([
                'firstBulkShipBatchOrder.order.orderItems.product.productImages',
                'lockTask.user.contactInformation',
                'printedUser.contactInformation',
                'packedUser.contactInformation',
            ])
            ->whereIn('customer_id', $customers->pluck('id')->toArray())
            ->when(!empty($warehouseIds), function (Builder $builder) use ($warehouseIds) {
                $builder->whereIn('warehouse_id', $warehouseIds);
            })
            ->where('shipped_at', $shipped ? '!=' : '=', null)
            ->where('in_progress', $inProgress)
            ->when($filterInputs['printed'] ?? null, function(Builder $query, $printed) {
                if ($printed === 'no') {
                    return $query->whereNull('printed_user_id');
                }

                return $query->whereNotNull('printed_user_id');
            })
            ->when($filterInputs['packed'] ?? null, function(Builder $query, $packed) {
                if ($packed === 'no') {
                    return $query->whereNull('packed_user_id');
                }

                return $query->whereNotNull('packed_user_id');
            })
            ->where(function(Builder $query) use ($filterInputs) {
                // Find by filter result
                // Start/End date
                if (Arr::get($filterInputs, 'start_date') || Arr::get($filterInputs, 'end_date')) {
                    $startDate = Carbon::parse($filterInputs['start_date'] ?? '1970-01-01')->startOfDay();
                    $endDate = Carbon::parse($filterInputs['end_date'] ?? Carbon::now())->endOfDay();

                    $query->whereBetween('bulk_ship_batches.updated_at', [$startDate, $endDate]);
                }
            })
            ->orderBy($sortColumnName, $sortDirection);

        $term = $request->get('search')['value'];

        if ($term) {
            $term = $term . '%';

            $bulkShipBatchQuery = $bulkShipBatchQuery->where(function ($query) use ($term) {
                $query->whereHas('orders', function ($query) use ($term) {
                    return $query->where('number', 'LIKE', $term);
                })->orWhereHas('orders.orderItems', function(Builder $query) use ($term) {
                    return $query->where('sku', 'LIKE', $term);
                })->orWhere('id', $term);
            });
        }

        if ($request->get('length') && ((int)$request->get('length')) !== -1) {
            $bulkShipBatchQuery = $bulkShipBatchQuery->skip($request->get('start'))->limit($request->get('length'));
        }

        $bulkShipBatchQuery = $bulkShipBatchQuery->get();
        $bulkShipBatchCollection = BulkShippingTableResource::collection($bulkShipBatchQuery);

        return response()->json([
            'data' => $bulkShipBatchCollection,
            'visibleFields' => app('editColumn')->getVisibleFields('bulk-shipping'),
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX,
        ]);
    }

    public function inProgress(Request $request)
    {
        return $this->index($request, false, true);
    }

    public function batches(Request $request)
    {
        return $this->index($request, true);
    }

    public function markAsPrinted(BulkShipBatch $bulkShipBatch)
    {
        $bulkShipBatch->update([
            'printed_user_id' => Auth::id(),
            'printed_at' => now(),
        ]);

        return response()->json([
            'message' => __('Batch marked as printed!'),
        ]);
    }

    public function markAsPacked(BulkShipBatch $bulkShipBatch)
    {
        $bulkShipBatch->update([
            'packed_user_id' => Auth::id(),
            'packed_at' => now(),
        ]);

        return response()->json([
            'message' => __('Batch marked as packed!'),
        ]);
    }

    public function unlock(BulkShipBatch $bulkShipBatch)
    {
        $this->authorize('canUnlockBatch', $bulkShipBatch);

        $bulkShipBatch->unlock();

        return response()->json([
            'success' => true,
            'message' => __('Batch was unlocked successfully!'),
        ]);
    }

    public function dataTable(Request $request, $batchId): JsonResponse
    {
        $bulkShipBatch = BulkShipBatch::find($batchId);
        $filterInputs =  $request->get('filter_form');

        $orders = $bulkShipBatch->orders();

        $term = $request->get('search')['value'];

        if ($term) {
            $term .= '%';

            $orders->where('number', 'like', $term);
        }

        if ($filterInputs) {
            match ($filterInputs['batch_filter.bulk_ship_order_status']) {
                'shipped' => $orders = $orders->whereNotNull('shipment_id'),
                'not_shipped', 'failed' => $orders = $orders->whereNotNull('status_message'),
                'all' => null
            };

            if ($filterInputs['batch_filter.shipping_method_id'] !== 'all') {
                $orders = $orders->where('shipping_method_id', $filterInputs['batch_filter.shipping_method_id']);
            }

            if ($filterInputs['batch_filter.shipping_carrier_id'] !== 'all') {
                $orders = $orders->whereHas('shippingMethod', function($query) use ($filterInputs) {
                    $query->where('shipping_carrier_id', $filterInputs['batch_filter.shipping_carrier_id']);
                });
            }
        }

        if ($request->get('length') && ((int) $request->get('length')) !== -1) {
            $orders = $orders->skip($request->get('start'))->limit($request->get('length'));
        }

        $orders = $orders->get();

        foreach ($orders as $order) {
            $order['batch_id'] = $bulkShipBatch->id;
            $order['batch_shipping_method_id'] = $bulkShipBatch->shipping_method_id;
        }

        return response()->json([
            'data' => BulkShipBatchOrderTableResource::collection($orders),
            'visibleFields' => app('editColumn')->getVisibleFields('bulk-ship-batch'),
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX
        ]);
    }
}
