<?php

namespace App\Http\Controllers;

use App\Enums\Source;
use App\Features\WholesaleEDI;
use App\Http\Dto\Filters\OrdersDataTableDto;
use App\Http\Requests\Csv\{ExportCsvRequest, ImportCsvRequest};
use App\Http\Requests\Order\{BulkEditRequest, BulkSelectionRequest, DestroyRequest, StoreRequest, UpdateRequest};
use App\Http\Requests\Order\StoreReturnRequest as StoreOrderReturnRequest;
use App\Http\Requests\Shipment\ReShipRequest;
use App\Http\Resources\OrderTableResource;
use App\Models\{Automation,
    Currency,
    Customer,
    CustomerSetting,
    EDI\Providers\CrstlPackingLabel,
    Order,
    OrderItem,
    OrderStatus,
    ShippingBox,
    ShippingCarrier,
    ShippingMethod,
    Warehouse};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\{JsonResponse, RedirectResponse, Request};
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Order::class);
    }

    public function index($keyword = '')
    {
        $customers = app('user')->getSelectedCustomers();
        $customerIds = $customers->pluck('id')->toArray();

        $shippingCarriers = ShippingCarrier::whereIn('customer_id', $customerIds)->get();

        $data = new OrdersDataTableDto(
            $customers,
            OrderStatus::whereIn('customer_id', $customerIds)->get(),
            $shippingCarriers->pluck('name')->unique(),
            ShippingMethod::whereIn('shipping_carrier_id',
                $shippingCarriers->pluck('id'))->get()->pluck('name')->unique(),
            ShippingBox::whereIn('customer_id', $customerIds)->get()->pluck('name', 'id')->unique(),
            Warehouse::whereIn('customer_id', $customerIds)->get(),
            Automation::query()
                ->whereIn('customer_id', $customerIds)
                ->get(['name', 'is_enabled'])
                ->map(function (Automation $name) {
                    return $name->is_enabled ? $name->name : $name->name . ' (Inactive)';
                })
        );

        return view('orders.index', [
            'page' => 'manage_orders',
            'keyword' => $keyword,
            'data' => $data,
            'datatableOrder' => app()->editColumn->getDatatableOrder('orders'),
        ]);
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function transferOrders(): \Illuminate\Contracts\View\View|Factory|Application
    {
        return view('orders.transfer_orders');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'ordered_at';
        $sortDirection = 'desc';
        $filterInputs = $request->get('filter_form', []);
        $term = Arr::get($request->get('search'), 'value');
        $filterInputs['term'] = $term;

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $orderCollection = app('order')->getQuery($filterInputs, $sortColumnName);

        $orderCollection = $orderCollection->orderBy(trim($sortColumnName), $sortDirection);

        if ($term) {
            $orderCollection = app('order')->searchQuery($term, $orderCollection);
        }

        $start = $request->get('start');
        $length = $request->get('length');

        if ($length == -1) {
            $length = 10;
        }

        if ($length) {
            $orderCollection = $orderCollection->skip($start)->limit($length);
        }

        $orders = $orderCollection->get();
        $visibleFields = app('editColumn')->getVisibleFields('orders');

        $orderCollection = OrderTableResource::collection($orders);

        return response()->json([
            'data' => $orderCollection,
            'visibleFields' => $visibleFields,
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX
        ]);
    }

    public function create()
    {
        return view('orders.create', [
            'page' => 'create_order',
        ]);
    }

    public function store(StoreRequest $request)
    {
        app('order')->store($request, source: Source::MANUAL_VIA_FORM);

        return redirect()->route('order.index')->withStatus(__('Order successfully created.'));
    }

    public function edit(Order $order)
    {
        $customer = $order->customer;

        $orderStatuses = $customer->orderStatuses;

        $orderStatuses->prepend([
            'id' => 'pending',
            'name' => 'Pending'
        ]);

        $partiallyShipped = app('order')->isOrderPartiallyShipped($order);
        $shippingMethods = app('shippingMethodMapping')->filterShippingMethods($customer);
        $shippingBoxes = app('shippingBox')->filterShippingBoxes($customer);
        $warehouses = app('warehouse')->filterWarehouses($customer);

        $order = $order->load([
            'orderItems.product.kitItems',
            'orderItems.kitOrderItems',
            'orderItems.toteOrderItems.user',
            'orderItems.toteOrderItems.pickingBatchItem',
        ]);

        $shipmentItemLots = [];
        $shipmentItemLocations = [];
        $shipmentItemSerialNumbers = [];
        $shipmentItemTotes = [];

        foreach ($order->shipments as $shipment) {

            foreach ($shipment->shipmentItems as $shipmentItem) {

                $shipmentItemLots[$shipmentItem->id] = '';
                $shipmentItemLocations[$shipmentItem->id] = '';
                $shipmentItemSerialNumbers[$shipmentItem->id] = '';
                $shipmentItemTotes[$shipmentItem->id] = '';

                $shipmentItem = $shipmentItem->load([
                    'orderItem.packageOrderItems.package' => function ($query) use ($shipment) {
                        return $query->where('shipment_id', $shipment->id);
                    }
                ]);

                foreach ($shipmentItem->orderItem->packageOrderItems as $packageOrderItem) {

                    if (!is_null($packageOrderItem->package) && $packageOrderItem->package->shipment_id == $shipment->id) {
                        if (!is_null($packageOrderItem->lot)) {
                            $shipmentItemLots[$shipmentItem->id] .= $packageOrderItem->lot->name.' ';
                        }
                        if (!is_null($packageOrderItem->location)) {
                            $shipmentItemLocations[$shipmentItem->id] .= $packageOrderItem->location->name.' ';
                        }
                        if (!is_null($packageOrderItem->serial_number)) {
                            $shipmentItemSerialNumbers[$shipmentItem->id] .= $packageOrderItem->serial_number.' ';
                        }
                        if (!is_null($packageOrderItem->tote)) {
                            $shipmentItemTotes[$shipmentItem->id] .= $packageOrderItem->tote->name.' ';
                        }
                    }
                }
            }
        }

        $isLockedForEditing = $order->isLockedForEditing();

        if ($customer->hasFeature(WholesaleEDI::class) && $order->is_wholesale) {
            $ediPackingLabels = CrstlPackingLabel::query()
                ->whereHas('asn', function (Builder $query) use ($order) {
                    $query->where('order_id', $order->id)
                        ->select(['id', 'shipment_id']);
                })
                ->whereNotNull('content')
                ->get(['id', 'asn_id'])
                ->mapWithKeys(fn(CrstlPackingLabel $packingLabel) => [
                    $packingLabel->asn->shipment_id => [
                        'url' => route('shipment.packing-label', [
                            'shipment' => $packingLabel->asn->shipment_id,
                            'asn' => $packingLabel->asn_id,
                            'packingLabel' => $packingLabel->id
                        ]),
                        'name' => __('EDI Packing label')
                    ]
                ]);
        }

        return view('orders.edit', [
            'order' => $order,
            'page' => 'manage_orders',
            'orderStatuses' => $orderStatuses,
            'shippingMethods' => $shippingMethods,
            'shippingBoxes' => $shippingBoxes,
            'warehouses' => $warehouses,
            'partiallyShipped' => $partiallyShipped,
            'shipmentItemLots' => $shipmentItemLots,
            'isLockedForEditing' => $isLockedForEditing,
            'shipmentItemLocations' => $shipmentItemLocations,
            'shipmentItemSerialNumbers' => $shipmentItemSerialNumbers,
            'shipmentItemTotes' => $shipmentItemTotes,
            'currency' => $order->currency->symbol ?? Currency::find(customer_settings($order->customer->id,
                    CustomerSetting::CUSTOMER_SETTING_CURRENCY))->symbol ?? '',
            'ediPackingLabels' => $ediPackingLabels ?? [],
        ]);
    }

    public function showOrderChannelPayload(Order $order)
    {
        $this->authorize('view', $order);

        return response(json_encode($order->order_channel_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
            ->header('Content-Type', 'text/plain');
    }

    public function showRawData(Order $order): void
    {
        $this->authorize('viewRawData', $order);

        $order->loadMissing(
            'customer.contactInformation',
            'orderChannel',
            'shippingMethod',
            'orderItems.product',
            'shipments.shipmentItems',
            'shippingContactInformation',
            'billingContactInformation',
            'orderLock',
            'tags',
            'bulkShipBatch',
            'shippingBox',
            'actedOnByAutomations'
        );

        dump($order->toArray());
    }

    public function getOrderReturnForm(Order $order): \Illuminate\Contracts\View\View
    {
        $customer = $order->customer;
        $orderStatuses = $customer->orderStatuses;

        $orderStatuses->prepend([
            'id' => 'pending',
            'name' => 'Pending'
        ]);

        $shippedOrderItems = app('order')->getShippedOrderItems($order);
        $warehouse = Warehouse::where('customer_id',
            $customer->parent_id ? $customer->parent_id : $customer->id)->first();
        $shippingMethods = app('shippingMethodMapping')->filterShippingMethods($customer);
        $returnShippingMethod = app('shippingMethodMapping')->returnShippingMethod($order);

        return View::make('shared.modals.components.orders.returnForm', [
            'order' => $order->load('orderItems.product.kitItems', 'orderItems.kitOrderItems',
                'orderItems.toteOrderItems'),
            'page' => 'manage_orders',
            'shippedOrderItems' => $shippedOrderItems,
            'orderStatuses' => $orderStatuses,
            'defaultReturnStatus' => [
                'id' => $orderStatuses->first()['id'] ?? '',
                'text' => $orderStatuses->first()['name'] ?? ''
            ],
            'defaultWarehouse' => [
                'id' => $warehouse->id,
                'text' => $warehouse->contactInformation->name.', '.$warehouse->contactInformation->email.', '.$warehouse->contactInformation->zip.', '.$warehouse->contactInformation->city
            ],
            'shippingMethods' => $shippingMethods,
            'returnShippingMethod' => $returnShippingMethod
        ]);
    }

    public function update(UpdateRequest $request, Order $order)
    {
        $updatedOrder = app('order')->update($request, $order, source: Source::MANUAL_VIA_FORM);

        return response()->json([
            'success' => true,
            'message' => __('Order successfully updated.'),
            'order' => $updatedOrder
        ]);
    }

    public function destroy(DestroyRequest $request, Order $order)
    {
        app('order')->destroy($request, $order);

        return redirect()->route('order.index')->withStatus(__('Order successfully deleted.'));
    }

    public function filterCustomers(Request $request)
    {
        return app('order')->filterCustomers($request);
    }

    public function filterProducts(Request $request, Customer $customer = null)
    {
        return app('order')->filterProducts($request, $customer);
    }

    public function getOrderStatus(Request $request, Customer $customer)
    {
        return app('order')->getOrderStatus($request, $customer);
    }

    public function getBulkOrderStatus(Request $request)
    {
        return app('order')->getBulkOrderStatus($request);
    }

    public function webshipperShippingRates(Order $order)
    {
        return app('shipment')->webshipperShippingRates($order);
    }

    /**
     * @param  Order  $order
     * @return RedirectResponse
     */
    public function cancelOrder(Order $order): RedirectResponse
    {
        app('order')->cancelOrder($order);

        return redirect()->route('order.edit', ['order' => $order->id])->withStatus(__('Order successfully canceled.'));
    }

    /**
     * @param  Order  $order
     * @param  OrderItem  $orderItem
     * @return JsonResponse
     */
    public function cancelOrderItem(Order $order, OrderItem $orderItem): JsonResponse
    {
        app('order')->cancelOrderItem($orderItem, true);

        return response()->json([
            'success' => true,
            'message' => __('Order item successfully canceled.')
        ]);
    }

    /**
     * @param  Order  $order
     * @return RedirectResponse
     */
    public function uncancelOrder(Order $order): RedirectResponse
    {
        app('order')->uncancelOrder($order);

        return redirect()->route('order.edit',
            ['order' => $order->id])->withStatus(__('Order successfully uncanceled.'));
    }

    /**
     * @param  Order  $order
     * @param  OrderItem  $orderItem
     * @return JsonResponse
     */
    public function uncancelOrderItem(Order $order, OrderItem $orderItem): JsonResponse
    {
        app('order')->uncancelOrderItem($orderItem, true);

        return response()->json([
            'success' => true,
            'message' => __('Order item successfully uncanceled.')
        ]);
    }

    /**
     * @param  Order  $order
     * @return RedirectResponse
     */
    public function fulfillOrder(Order $order): RedirectResponse
    {
        app('order')->markAsFulfilled($order);

        return redirect()->route('order.edit', ['order' => $order->id])->withStatus(__('Order marked as fulfilled.'));
    }

    /**
     * @param  Order  $order
     * @return RedirectResponse
     */
    public function unfulfillOrder(Order $order): RedirectResponse
    {
        app('order')->markAsUnfulfilled($order);

        return redirect()->route('order.edit', ['order' => $order->id])->withStatus(__('Order marked as unfulfilled.'));
    }

    /**
     * @param  Order  $order
     * @return mixed
     */
    public function archiveOrder(Order $order): mixed
    {
        app('order')->archiveOrder($order);

        return redirect()->route('order.edit', ['order' => $order->id])->withStatus(__('Order successfully archived.'));
    }

    /**
     * @param  Order  $order
     * @return mixed
     */
    public function unarchiveOrder(Order $order): mixed
    {
        app('order')->unarchiveOrder($order);

        return redirect()->route('order.edit',
            ['order' => $order->id])->withStatus(__('Order successfully unarchived.'));
    }

    public function unlockOrder(Order $order): mixed
    {
        $this->authorize('unlock', $order);

        app('order')->unlockOrder($order);

        return redirect()->route('order.edit', ['order' => $order->id])->withStatus(__('Order unlocked.'));
    }

    public function getItem(Order $order): \Illuminate\Contracts\View\View
    {
        return View::make('shared.modals.components.orderDetails', compact('order'));
    }

    public function getKitItems(OrderItem $orderItem): \Illuminate\Contracts\View\View
    {
        return View::make('shared.modals.showDynamicKitItems', compact('orderItem'));
    }

    public function getOrderSlip(Order $order)
    {
        return app('order')->getOrderSlip($order);
    }

    /**
     * @param  ImportCsvRequest  $request
     * @return JsonResponse
     */
    public function importCsv(ImportCsvRequest $request): JsonResponse
    {
        $message = app('order')->importCsv($request);

        return response()->json([
            'success' => true,
            'message' => __($message)
        ]);
    }

    /**
     * @param  ExportCsvRequest  $request
     * @return mixed
     */
    public function exportCsv(ExportCsvRequest $request)
    {
        return app('order')->exportCsv($request);
    }

    public function reship(Order $order, ReShipRequest $request)
    {
        $this->authorize('reship', $order);

        $reshippedItemsNum = app('order')->reshipOrderItems($order, $request);

        if ($reshippedItemsNum > 0) {
            $status = __('Order item'.($reshippedItemsNum > 1 ? 's' : '').' successfully re-shipped.');
        } else {
            $status = __('Something went wrong');
        }

        return redirect()->route('order.edit', ['order' => $order->id])->withStatus($status);
    }

    /**
     * @param  Order  $order
     * @param  StoreOrderReturnRequest  $request
     * @return JsonResponse
     */
    public function return(Order $order, StoreOrderReturnRequest $request): JsonResponse
    {
        $return = app('return')->storeOrderReturn($order, $request);

        if (is_null($return)) {
            throw new HttpException(500, __('An error has occurred'));
        }

        if (!empty($return->returnLabels) && count($return->returnLabels) > 0 && $request->get('own_label') === '0') {

            $returnLabels = [];

            foreach ($return->returnLabels as $key => $returnLabel) {
                $returnLabels[] = [
                    'url' => route('return.label', [
                        'return' => $return,
                        'returnLabel' => $returnLabel
                    ]),
                    'name' => __('Label :number', ['number' => $key + 1])
                ];
            }

            if (count($returnLabels) > 0) {
                // Send email
                app('return')->sendReturnOrderWithLabelsMail($return, $returnLabels);

                return response()->json([
                    'success' => true,
                    'message' => __('Order successfully returned with labels. Email will be send with return label information')
                ]);
            }
        }

        if ($request->get('own_label') === '1') {
            return response()->json([
                'success' => true,
                'message' => __('Order successfully returned')
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('An error has occurred')
        ]);
    }

    /**
     * @param  BulkEditRequest  $request
     * @return void
     * @throws AuthorizationException
     */
    public function bulkEdit(BulkEditRequest $request)
    {
        /**
         * Need to make a proper policy for doing bulk edits but let's work on it when we start building actual roles and permissions
         */
//        $this->authorize('update', Order::class);

        app('order')->bulkEdit($request);
    }

    /**
     * @param  BulkSelectionRequest  $request
     * @return void
     * @throws AuthorizationException
     */
    public function bulkCancel(BulkSelectionRequest $request)
    {
//        $this->authorize('update', Order::class);

        app('order')->bulkCancel($request);
    }

    /**
     * @param  BulkSelectionRequest  $request
     * @return void
     * @throws AuthorizationException
     */
    public function bulkFulfill(BulkSelectionRequest $request)
    {
//        $this->authorize('update', Order::class);

        app('order')->bulkFulfill($request);
    }

    /**
     * @param  BulkSelectionRequest  $request
     * @return void
     * @throws AuthorizationException
     */
    public function bulkArchive(BulkSelectionRequest $request)
    {
//        $this->authorize('update', Order::class);

        app('order')->bulkArchive($request);
    }

    /**
     * @param  BulkSelectionRequest  $request
     * @return void
     * @throws AuthorizationException
     */
    public function bulkUnarchive(BulkSelectionRequest $request)
    {
//        $this->authorize('update', Order::class);

        app('order')->bulkUnarchive($request);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function countRecords(Request $request): JsonResponse
    {
        $filterInputs = $request->get('filter_form');
        $term = Arr::get($request->get('search'), 'value');
        $filterInputs['term'] = $term;

        $orderCollection = app('order')->getQuery($filterInputs)
            ->setEagerLoads([]);

        if ($term) {
            $orderCollection = app('order')->searchQuery($term, $orderCollection);
        }

        $results = __('Total records:').' '.$orderCollection->getQuery()->getCountForPagination();

        return response()->json([
            'results' => $results
        ]);
    }

    /**
     * @param  Order  $order
     * @return \Illuminate\Contracts\View\View
     */
    public function getShippingAddress(Order $order): \Illuminate\Contracts\View\View
    {
        return View::make('shared.modals.components.bulk_shipping.shipping_address_form', compact('order'));
    }
}
