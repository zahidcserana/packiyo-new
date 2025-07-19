<?php

namespace App\Http\Controllers;

use App\Http\Dto\Filters\ReturnsDataTableDto;
use App\Http\Requests\ReturnItem\FilterRequest as ProductReturnFilterRequest;
use App\Http\Requests\Return_\BulkEditRequest;
use App\Http\Requests\Return_\DestroyRequest;
use App\Http\Requests\Return_\ReceiveBatchRequest;
use App\Http\Requests\Return_\StoreRequest;
use App\Http\Requests\Return_\UpdateRequest;
use App\Http\Requests\Return_\UpdateStatusRequest;
use App\Http\Resources\ReturnTableResource;
use App\Http\Resources\ProductReturnTableResource;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\Return_;
use App\Models\ReturnItem;
use App\Models\ReturnLabel;
use App\Models\ReturnStatus;
use App\Models\ShipmentTracking;
use App\Models\Warehouse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Csv\ExportCsvRequest;

class ReturnController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Return_::class);

        foreach ($this->middleware as $key => $value) {
            if (isset($value['middleware']) && str_contains($value['middleware'], ',return_')) {
                $this->middleware[$key]['middleware'] = str_replace(',return_', ',return', $value['middleware']);
            }
        }
    }

    public function index($keyword='')
    {
        $customers = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        $data = new ReturnsDataTableDto(
            ReturnStatus::whereIn('customer_id', $customers)->get()->pluck('name', 'id'),
            Warehouse::whereIn('customer_id', $customers)->whereHas('contactInformation')->get(),
            Product::whereIn('customer_id', $customers)->get(),
        );

        return view('returns.index', [
            'page' => 'returns',
            'keyword'=> $keyword,
            'data' => $data,
            'datatableOrder' => app('editColumn')->getDatatableOrder('returns'),
        ]);
    }

    public function dataTable(Request $request): JsonResponse
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'returns.id';
        $sortDirection = 'desc';
        $filterInputs =  $request->get('filter_form');

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $returnOrdersCollection = app('return')->getQuery($filterInputs, $sortColumnName, $sortDirection);

        $term = $request->get('search')['value'];

        if ($term) {
            $returnOrdersCollection = app('return')->searchQuery($term, $returnOrdersCollection);
        }

        if ($request->get('length') && ((int) $request->get('length')) !== -1) {
            $returnOrdersCollection = $returnOrdersCollection
                ->skip($request->get('start'))
                ->limit($request->get('length'));
        }

        $returns = $returnOrdersCollection->get();

        return response()->json([
            'data' => ReturnTableResource::collection($returns->load('items.product')),
            'visibleFields' => app('editColumn')->getVisibleFields('returns'),
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX,
        ]);
    }

    public function show(Return_ $return): string
    {
        return view('returns.show')
            ->with([
                'return' => $return->load([
                    'warehouse.contactInformation',
                    'items',
                    'tags'
                ])
            ])
            ->render();
    }

    /**
     * @param Return_ $return
     * @param string $keyword
     * @return Application|Factory|View|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function edit(Return_ $return, string $keyword = '')
    {
        return view('returns.edit', ['return' => $return, 'keyword' => $keyword]);
    }

    public function status(Return_ $return): string
    {
        return view('returns.show')
            ->with([
                'status' => true,
                'return' => $return->load([
                    'warehouse.contactInformation',
                    'items',
                ])
            ])
            ->render();
    }

    public function statusUpdate(UpdateStatusRequest $request, Return_ $return): JsonResponse
    {
        app('return')->updateStatus($request, $return);

        return response()->json([
            'success' => true,
            'message' => __('Return status successfully updated.')
        ]);
    }

    public function create(Return_ $return, Order $order = null)
    {
        $currentOrder = $order;

        if (is_null($order)) {
            $currentOrder = Order::query()->first();
        } else {
            $orderItems = app('return')->createReturnFromOrder($order);
        }

        $warehouse = Warehouse::query()->first();

        $data = [
            'status' => true,
            'defaultOrder' => [
                'id' => $currentOrder->id,
                'text' => $currentOrder->number
            ],
            'defaultWarehouse' =>  [
                'id' => $warehouse->id,
                'text' => $warehouse->contactInformation->name . ', ' . $warehouse->contactInformation->email . ', ' . $warehouse->contactInformation->zip . ', ' . $warehouse->contactInformation->city
            ],
            'order' => $order,
            'orderItems' => $orderItems ?? null,
        ];

        return view('returns.create', $data);
    }

    public function createFromTracking(ShipmentTracking $shipmentTracking)
    {
        $order = $shipmentTracking->shipment->order;
        $warehouse = Warehouse::whereCustomerId(app()->user->getSelectedCustomers()->pluck('id')->toArray())->first();

        $data = [
            'status' => true,
            'shipmentTrackingId' => $shipmentTracking->id,
            'order' => $order,
            'defaultWarehouse' => [
                'id' => $warehouse->id,
                'text' => $warehouse->contactInformation->name . ', ' . $warehouse->contactInformation->email . ', ' . $warehouse->contactInformation->zip . ', ' . $warehouse->contactInformation->city
            ],
        ];

        return view('returns.create', $data);
    }

    public function store(StoreRequest $request): RedirectResponse
    {
        app('return')->store($request);

        return redirect()->route('return.index');
    }

    public function update(UpdateRequest $request, Return_ $return)
    {
        $customer = app('user')->getSelectedCustomers();

        app('return')->update($request, $return);

        app('return')->updateStatus($request, $return);

        $record = [];
        $order_item = null;

        foreach ($request['items'] as $item) {
            if (!empty($item['destination_id']) && !empty($item['quantity_to_receive'])) {
                if (!isset($item['order_item_id'])) {
                    $order_item = ReturnItem::where('product_id', $item['product_id'])->where('return_id', $return->id)->first();
                }
                $record[] = [
                    'source_id' => $return->id,
                    'source_type' => Return_::class,
                    'destination_id' => $item['destination_id'],
                    'destination_type' => Location::class,
                    'user_id' => Auth::user()->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity_to_receive'],
                    'return_item_id' => $item['return_item_id'] ?? $order_item->id,
                    'quantity_received' => $item['quantity_to_receive'],
                    'location_id' => $item['destination_id'],
                    'customer_id' => $customer->first()->id
                ];
            }
        }

        app('return')->receiveBatch(
            ReceiveBatchRequest::make($record),
            $return
        );

        return redirect()->back()->withStatus(__('Return successfully updated.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyRequest $request
     * @param Return_ $return
     * @return Response
     */

    public function destroy(DestroyRequest $request, Return_ $return): Response
    {
        app('return')->destroy($request, $return);

        return redirect()->route('return.index')->withStatus(__('Return successfully deleted.'));
    }

    public function filterOrders(Request $request)
    {
        return app('return')->filterOrders($request);
    }

    public function filterStatuses(Request $request)
    {
        return app('return')->filterStatuses($request);
    }

    public function filterOrderProducts(Request $request, $orderId)
    {
        return app('return')->filterOrderProducts($request, $orderId);
    }

    public function filterLocations(Request $request)
    {
        return app('return')->filterLocations($request);
    }

    /**
     * @param BulkEditRequest $request
     * @return mixed
     * @throws AuthorizationException
     */
    public function bulkEdit(BulkEditRequest $request)
    {
        /**
         * Need to make a proper policy for doing bulk edits but let's work on it when we start building actual roles and permissions
         */
//        $this->authorize('update', Return_::class);

        return app('return')->bulkEdit($request);
    }

    public function label(Return_ $return, ReturnLabel $returnLabel)
    {
        return app('return')->label($return, $returnLabel);
    }

    public function returnItemsByProduct(ProductReturnFilterRequest $request)
    {
        $input = $request->validated();
        $this->authorize('returnItemsByProduct', [Return_::class, $input['product_id']]);

        return view('returns.product_returns', [
            'datatableProductReturn' => app()->editColumn->getDatatableOrder('product-returns')
        ]);
    }

    /**
     * @param Request $request
     * @param Product|null $product
     * @return JsonResponse
     */
    public function productDataTable(Request $request, Product $product): JsonResponse
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'return_items.created_at';
        $sortDirection = 'desc';
        $filterInputs['from_date_created'] =  $request->get('from_date_created');
        $filterInputs['to_date_created'] =  $request->get('to_date_created');

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $returnItemsCollection = app('return')->getReturnItemsByProductQuery($product, $filterInputs, $sortColumnName, $sortDirection);

        $term = $request->get('search')['value'];

        if ($term) {
            $term = $term . '%';

            $returnItemsCollection->where(function ($query) use ($term) {
                $query->whereHas('return_.order', function ($query) use ($term) {
                    $query->where('number', 'like', $term);
                });
            });
        }

        $visibleFields = app('editColumn')->getVisibleFields('product-returns');
        $returnItemsCollection = ProductReturnTableResource::collection($returnItemsCollection->get());

        return response()->json([
            'data' => $returnItemsCollection,
            'visibleFields' => $visibleFields,
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX
        ]);
    }

    /**
     * @param ExportCsvRequest $request
     * @return mixed
     */
    public function exportCsv(ExportCsvRequest $request)
    {
        return app('return')->exportCsv($request);
    }

    public function getReturnByTrackingNumber($trackingNumber): JsonResponse
    {
        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        $shipmentTracking = ShipmentTracking::whereHas(
            'shipment',
            static function (Builder $builder) use ($customerIds) {
                $builder->whereHas('customer', function (Builder $builder) use ($customerIds) {
                    return $builder->whereIn('orders.customer_id', $customerIds);
                });
            })
            ->where('tracking_number', trim($trackingNumber))
            ->first();

        if ($shipmentTracking) {
            $order = $shipmentTracking->shipment->order;
            $return = Return_::where('order_id', $order->id)
                ->where('shipment_tracking_id', $shipmentTracking->id)
                ->first();

            if ($return) {
                $redirect = route('return.edit', ['return' => $return]);
            } else {
                $redirect = route('return.createFromTracking', ['shipmentTracking' => $shipmentTracking]);
            }

            return response()->json(['success' => true, 'redirect' => $redirect]);
        }

        return response()->json(['success' => false]);
    }
}
