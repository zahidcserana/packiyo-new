<?php

namespace App\Http\Controllers;

use App\Mail\OrderShipped;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Pennant\Feature;
use App\Components\{PackingComponent, WholesaleComponent};
use App\Exceptions\{ShippingException, WholesaleException};
use App\Features\{MultiWarehouse, WholesaleEDI, RequiredReadyToPickForPacking};
use App\Http\Requests\{Packing\BulkShipStoreRequest, Packing\GetShippingRatesRequest, Packing\StoreRequest};
use App\Http\Resources\PackingSingleOrderShippingTableResource;
use App\Jobs\CreateBulkShipOrderShipmentJob;
use Carbon\Carbon;
use App\Models\{BulkShipBatch,
    BulkShipBatchOrder,
    CustomerSetting,
    Order,
    Shipment,
    ShipmentLabel,
    ShippingCarrier,
    Printer,
    EDI\Providers\CrstlASN,
    EDI\Providers\CrstlEDIProvider,
    EDI\Providers\CrstlPackingLabel,
    Task};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\{Foundation\Application, View\Factory, View\View};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\{Facades\Auth, Arr, Facades\Log, Facades\Mail, Facades\Session, Facades\Bus, Facades\DB};
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class PackingController extends Controller
{
    public function __construct(protected WholesaleComponent $wholesaleComponent)
    {
        $this->authorizeResource(Order::class);
    }

    public function index()
    {
        $this->authorize('viewAny', Order::class);

        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        $shipments = Shipment::whereHas('order', static function (Builder $query) use ($customerIds) {
                $query->whereIn('customer_id', $customerIds);
            })

            ->with(['order', 'shippingMethod', 'shipmentTrackings', 'shipmentLabels'])
            ->where('user_id', auth()->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('packing.index', [
            'shipments' => $shipments,
            'page' => 'packing_single_order_shipping',
            'datatableOrder' => app()->editColumn->getDatatableOrder('packing-single-order-shipping'),
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function singleOrderShippingDataTable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'orders.ordered_at';
        $sortDirection = 'asc';
        $filterInputs = $request->get('filter_form');

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $sessionCustomer = app('user')->getSessionCustomer();
        $customers = app('user')->getSelectedCustomers()->pluck('id')->toArray();
        $warehouseId = null;

        if ($sessionCustomer) {
            $warehouseId = app('user')->getCustomerWarehouseId($sessionCustomer);
        }

        $orderQuery = Order::with('orderItems.product.productImages', 'customer.contactInformation')
            ->whereDoesntHave('orderItems.pickingBatchItems.pickingBatch.tasks', function ($query) {
                $query->whereNull('completed_at');
            })
            ->whereDoesntHave('orderLock')
            ->withCount('orderItems')
            ->when(Feature::for('instance')->active(RequiredReadyToPickForPacking::class), function ($query) {
                $query->where('ready_to_pick', 1);
            })
            ->whereIn('customer_id', $customers)
            ->where('ready_to_ship', 1)
            ->when($filterInputs, function ($query) use ($filterInputs) {
                // Ordered at
                if (Arr::get($filterInputs, 'ordered_at')) {
                    $query->where('ordered_at', '>=', Carbon::parse($filterInputs['ordered_at'])->toDateString());
                }

                // Required ship date
                if (Arr::get($filterInputs, 'ship_before')) {
                    $query->where('ship_before', Carbon::parse($filterInputs['ship_before'])->toDateString());
                }
            })
            ->groupBy('orders.id')
            ->orderBy($sortColumnName, $sortDirection);

        if ($warehouseId) {
            $orderQuery = $orderQuery->where('warehouse_id', $warehouseId);
        }

        $term = $request->get('search')['value'];

        if ($term) {
            $term .= '%';
            $orderQuery = $orderQuery->where('number', 'like', $term);
        }

        if ($request->get('length') && ((int) $request->get('length')) !== -1) {
            $orderQuery = $orderQuery->skip($request->get('start'))->limit($request->get('length'));
        }

        $orderQuery = $orderQuery->get();
        $orderCollection = PackingSingleOrderShippingTableResource::collection($orderQuery);

        return response()->json([
            'data' => $orderCollection,
            'visibleFields' => app('editColumn')->getVisibleFields('packing-single-order-shipping'),
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX
        ]);
    }

    public function barcodeSearch($barcode): JsonResponse
    {
        $order = app('packing')->barcodeSearch($barcode);

        if ($order) {
            if ($order->orderLock) {
                return response()->json(['success' => true, 'order_lock' => true]);
            }

            return response()->json(['success' => true, 'redirect' => route('packing.single_order_shipping', ['order' => $order->id])]);
        }

        return response()->json(['success' => false]);
    }

    /**
     * @param Request $request
     * @param Order $order
     * @param BulkShipBatch|null $bulkShipBatch
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function singleOrderShipping(Request $request, Order $order, BulkShipBatch $bulkShipBatch = null)
    {
        $totes = [];
        $toteOrderItemArr = [];

        $this->authorize('singleOrderShipping', $order);

        $customer = $order->customer;

        $shippingMethods = collect($customer->shippingMethods);
        $shippingBoxes = $customer->availableShippingBoxes();

        if ($customer->parent_id) {
            $shippingMethods = $shippingMethods->merge($customer->parent->shippingMethods);
        }

        $shippingCarriers = [];

        foreach ($shippingMethods as $method) {
            if (!array_key_exists($method->shipping_carrier_id, $shippingCarriers)) {
                $shippingCarriers[$method->shipping_carrier_id] = ShippingCarrier::find($method->shipping_carrier_id)->name;
            }
        }

        $order = $order->load([
            'orderItems.product.productImages',
            'orderItems.product.locations' => function ($query) use ($order) {
                if (Feature::for('instance')->active(MultiWarehouse::class)) {
                    return $query->where('warehouse_id', $order->warehouse_id)->with(['warehouse', 'lotItems.lot']);
                } else {
                    return $query->with(['lotItems.lot']);
                }
            },
            'orderItems.placedToteOrderItems.location',
            'shippingContactInformation.country',
            'bulkShipBatch',
        ]);

        $printers = $customer->printers;

        if ($customer->parent_id) {
            $printers = $printers->merge($customer->parent->printers);
        }

        foreach ($order->orderItems as $key => $orderItem) {
            if (!$orderItem->product) {
                Log::warning("Won't request non-product line {$orderItem->sku} from order {$order->number} to pack");

                continue;
            }

            $toteOrderItemArr[$orderItem->id]['total_picked'] = 0;
            $toteOrderItemArr[$orderItem->id]['total_in_totes'] = 0;

            if ($orderItem->quantity_allocated > 0) {

                foreach ($orderItem->placedToteOrderItems as $toteOrderItem) {
                    $toteOrderItemArr[$orderItem->id]['total_in_totes'] += $toteOrderItem->quantity;

                    if (!empty($toteOrderItem->tote)) {
                        $totes[$toteOrderItem->tote->name] = $toteOrderItem->tote->name;
                    }
                }

                foreach ($orderItem->product->locations as $location) {
                    if (isset($orderItem->placedToteOrderItems)) {
                        foreach ($orderItem->placedToteOrderItems as $toteOrderItem) {

                            if ($toteOrderItem->location_id == $location->id) {

                                $toteLocationIndex = $location->id . '-' . $toteOrderItem->tote->id;

                                if (isset($toteOrderItemArr[$orderItem->id]['locations'][$toteLocationIndex])) {
                                    $toteOrderItemArr[$orderItem->id]['locations'][$toteLocationIndex]['tote_order_item_quantity'] += $toteOrderItem->quantity;
                                    $toteOrderItemArr[$orderItem->id]['locations'][$toteLocationIndex]['tote_name'] .= ', ' . $toteOrderItem->tote->name;
                                } else {
                                    $toteOrderItemArr[$orderItem->id]['locations'][$toteLocationIndex] = [
                                        'key' => $key,
                                        'order_item' => $orderItem,
                                        'tote_order_item' => $toteOrderItem,
                                        'tote_order_item_quantity' => $toteOrderItem->quantity,
                                        'tote_name' => $toteOrderItem->tote->name,
                                        'tote_id' => $toteOrderItem->tote->id,
                                    ];
                                }

                                $toteOrderItemArr[$orderItem->id]['total_picked'] += $toteOrderItem->quantity;
                            }
                        }
                    }
                }
            }
        }

        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        $shipments = Shipment::whereHas('order', function(Builder $query) use ($customerIds) {
            $query->whereIn('customer_id', $customerIds);
        })
            ->with(['order', 'shippingMethod', 'shipmentTrackings', 'shipmentLabels'])
            ->where('user_id', auth()->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $defaultShippingBox = $order->getDefaultShippingBox();

        $onlyUseBulkShipPickableLocations = customer_settings($order->customer->id, \App\Models\CustomerSetting::CUSTOMER_SETTING_ONLY_USE_BULK_SHIP_PICKABLE_LOCATIONS) == "1";

        if ($order->customer->parent) {
            $onlyUseBulkShipPickableLocations = customer_settings($order->customer->parent_id, \App\Models\CustomerSetting::CUSTOMER_SETTING_ONLY_USE_BULK_SHIP_PICKABLE_LOCATIONS) == "1";
        }

        $isWholesale = false;

        $customer = $order->getShippingCustomer();


        if ($customer->hasFeature(WholesaleEDI::class) && $order->is_wholesale) {
            $isWholesale = $order->is_wholesale;
        }

        $requiredReadyToPickForPacking = Feature::for('instance')->active(RequiredReadyToPickForPacking::class);

        return view('packing.shipping', [
            'order' => $order,
            'bulkShipBatch' => $bulkShipBatch,
            'shippingBoxes' => $shippingBoxes,
            'shippingCarriers' => $shippingCarriers,
            'defaultShippingBox' => $defaultShippingBox,
            'shippingMethods' => collect($shippingMethods),
            'page' => 'packing_single_order_shipping',
            'printers' => $printers,
            'toteOrderItemArr' => $toteOrderItemArr,
            'shipments' => $shipments,
            'totes' => implode(', ', $totes),
            'onlyUseBulkShipPickableLocations' => $onlyUseBulkShipPickableLocations,
            'isWholesale' => $isWholesale,
            'requiredReadyToPickForPacking' => $requiredReadyToPickForPacking,
        ]);
    }

    /**
     * @param StoreRequest $request
     * @param Order $order
     * @param BulkShipBatch|null $bulkShipBatch
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function singleOrderShip(StoreRequest $request, Order $order, BulkShipBatch $bulkShipBatch = null): JsonResponse
    {
        try {
            $shipments = app(PackingComponent::class)->packAndShip($order, $request);

            $printables = [
                'labels' => [],
                'documents' => []
            ];

            if ($shipments) {
                foreach ($shipments as $shipment) {
                    if (count($shipment->shipmentLabels) > 0) {
                        Session::flash('status', 'Shipment successfully created!');

                        $labelNumber = 1;

                        foreach ($shipment->shipmentLabels as $shipmentLabel) {
                            if ($shipmentLabel->type === ShipmentLabel::TYPE_RETURN) {
                                $labelName = __('Return label');
                            } else {
                                $labelName = __('Label :number', ['number' => $labelNumber++]);
                            }

                            $printables['labels'][] = [
                                'url' => route('shipment.label', [
                                    'shipment' => $shipment,
                                    'shipmentLabel' => $shipmentLabel
                                ]),
                                'name' => $labelName,
                                'shipment_id' => $shipment->id
                            ];
                        }

                        Order::auditShipment($shipment);

                        if ($request->print_packing_slip) {
                            $printables['documents'][] = [
                                'url' => route('shipment.getPackingSlip', [
                                    'shipment' => $shipment,
                                ]),
                                'name' => __('Packing slip'),
                                'shipment_id' => $shipment->id
                            ];

                            if ($order->custom_invoice_url) {
                                $printables['documents'][] = [
                                    'url' => $order->custom_invoice_url,
                                    'name' => __('Invoice'),
                                    'shipment_id' => $shipment->id
                                ];
                            }
                        }

                        if (!$order->shippingBox) {
                            $order->shipping_box_id = $request->shipping_box;
                            $order->save();
                        }
                    }

                    $printables = array_merge($printables, $this->getPackageDocumentsForPrinting($shipment));

                    if (customer_settings($shipment->order->customer_id, CustomerSetting::CUSTOMER_SETTING_SHIPPING_NOTIFICATIONS_FOR_MANUAL_ORDERS) === '1') {
                        if ($shipment->contactInformation->email && !$order->order_channel_id) {
                            Mail::to($shipment->contactInformation->email)->send(new OrderShipped($shipment));
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'labels' => $printables['labels'],
                    'documents' => $printables['documents']
                ]);
            }
        } catch (ShippingException $exception) {
            throw new HttpException(500, $exception->getMessage());
        }

        throw new HttpException(500, __('Couldn\'t ship using selected shipping method'));
    }

    /**
     * @param Request $request
     * @param BulkShipBatch $bulkShipBatch
     * @return Application|Factory|View|JsonResponse|RedirectResponse
     * @throws AuthorizationException
     */
    public function bulkShipBatchShipping(Request $request, BulkShipBatch $bulkShipBatch)
    {
        $this->authorize('bulkShipBatchShipping', $bulkShipBatch);

        if ($bulkShipBatch->shipped_at) {
            app('packing')->getBulkShipPDF($bulkShipBatch);

            return redirect()->route('bulk_shipping.batches');
        }

        if ($request->ajax()) {
            $task = Task::find($bulkShipBatch->lock_task_id);
            $locked = $bulkShipBatch->lock_task_id && Auth::id() !== $task->user_id;

            if ($task && $locked) {
                $response = [
                    'locked' => true,
                    'title' => __('Batch is locked'),
                    'message' => __('This batch is currently locked by :user', [
                        'user' => $task->user->contactInformation->name,
                    ]),
                ];
            } else {
                $response = [
                    'locked' => false,
                ];
            }

            return response()->json($response);
        }

        if (!app('user')->getSessionCustomer()) {
            return back()->withStatus([
                'type' => 'error',
                'message' => 'Customer is not set!',
            ]);
        }

        $bulkShipBatch->lock();

        return $this->singleOrderShipping(
            $request,
            $bulkShipBatch->orders()->wherePivotNull('shipment_id')->first() ?? $bulkShipBatch->orders->first(),
            $bulkShipBatch
        );
    }

    public function bulkShipBatchShip(BulkShipStoreRequest $storeRequest, BulkShipBatch $bulkShipBatch)
    {
        $this->authorize('bulkShipBatchShip', $bulkShipBatch);

        $bulkShipBatch = $bulkShipBatch->fresh()->load('orders');

        $this->resetFailedBulkShipBatchOrders($bulkShipBatch);

        $filteredOrders = $bulkShipBatch->orders
            ->filter(function($order) {
                return $order->pivot->shipment_id === null;
            });

        Log::channel('bulkshipping')->info('Preparing jobs. Orders count: ' . $filteredOrders->count());

        $jobBatch = Bus::batch([])
            ->allowFailures()
            ->onQueue(get_distributed_queue_name('bulkshipping'))
            ->name('bulk-shipping-' . $bulkShipBatch->id . '-' . time())
            ->dispatch();

        $userId = auth()->user()->id;
        $filteredOrders
            ->map(function($order) use ($jobBatch, $storeRequest, $bulkShipBatch, $userId) {
                if ($request = $storeRequest->getOrderRequestInstance($order->id)) {
                    Log::channel('bulkshipping')->info(
                        'Order ID: ' . $order->id . ' (Number: ' . $order->number . ') will be dispatched to the queue!'
                    );

                    return $jobBatch->add(new CreateBulkShipOrderShipmentJob(
                        $userId,
                        $bulkShipBatch,
                        $order,
                        $request->all()
                    ));
                }

                Log::channel('bulkshipping')->info(
                    'Order ID: ' . $order->id . ' (Number: ' . $order->number . ') will be skipped! Not enough inventory!'
                );
                $bulkShipBatch->orders()
                    ->updateExistingPivot($order, [
                        'status_message' => __('Not enough inventory!'),
                    ]);
                return null;
            })
            ->filter();

        Log::channel('bulkshipping')->info('Jobs count: ' . $jobBatch->totalJobs);

        Log::channel('bulkshipping')->info('Dispatch batch jobs to the queue.');
        $bulkShipBatch->update(['in_progress' => true]);

        return response()->json([
            'success' => true,
            'bulkShipBatchId' => $bulkShipBatch->id,
            'bulkShipOrderJobsDispatched' => true,
        ]);
    }

    public function bulkShipBatchProgress(Request $request, BulkShipBatch $bulkShipBatch)
    {
        $this->authorize('show', $bulkShipBatch);

        return app('packing')->bulkShipBatchProgress($bulkShipBatch, $request->limit);
    }

    private function resetFailedBulkShipBatchOrders(BulkShipBatch $bulkShipBatch): void
    {
        Log::channel('bulkshipping')->info('Reset failed orders progress (started_at, finished_at, status_message).');

        // remove duplicates if there are any.
        $duplicateBulkShipBatchOrderIds = $bulkShipBatch->orders()->get()
            ->groupBy('id')
            ->filter(static function (Collection $collection) {
                return $collection->count() > 1;
            })->map(static function (Collection $collection) {
                return $collection->last();
            })->pluck('pivot.id');

        BulkShipBatchOrder::whereIn('id', $duplicateBulkShipBatchOrderIds)->delete();

        DB::table('bulk_ship_batch_order')
            ->where('bulk_ship_batch_id', $bulkShipBatch->id)
            ->whereNull('shipment_id')
            ->update([
                'started_at' => null,
                'finished_at' => null,
                'status_message' => null,
            ]);
    }

    public function removeBatchOrder(BulkShipBatch $bulkShipBatch, Order $order)
    {
        if ($order->fulfilled_at) {
            return back()->with([
                'status' => [
                    'type' => 'error',
                    'message' => __('Order :order was not removed! Shipped order cannot be removed from the batch!', [
                        'order' => $order->number,
                    ]),
                ],
            ]);
        }

        $bulkShipBatch->orders()->detach($order->id);

        $order->orderLock()->delete();

        $bulkShipBatch->update([
            'total_orders' => $bulkShipBatch->orders()->count()
        ]);

        if ($bulkShipBatch->orders()->wherePivotNull('shipment_id')->count() === 0) {
            $bulkShipBatch->update([
                'shipped_at' => now(),
                'in_progress' => false,
            ]);
        }

        return back()->with([
            'status' => __('Order :order was successfully removed from the batch!', [
                'order' => $order->number,
            ]),
        ]);
    }

    /**
     * @param BulkShipBatch $bulkShipBatch
     * @return JsonResponse
     */
    public function closeBulkShipBatch(BulkShipBatch $bulkShipBatch): JsonResponse
    {
        $bulkShipBatch->unlockOrders();

        $failedOrdersQuery = DB::table('bulk_ship_batch_order')
            ->where('bulk_ship_batch_id', $bulkShipBatch->id)
            ->whereNull('shipment_id');

        $failedOrdersIds = $failedOrdersQuery->pluck('order_id')->toArray();

        $failedOrdersQuery->delete();

        $bulkShipBatch->update([
            'shipped_at' => now(),
            'in_progress' => false,
            'total_orders' => $bulkShipBatch->orders()->count(),
        ]);

        $labels = app('packing')->getBulkShipPDF($bulkShipBatch);

        return response()->json([
            'message' => __('Bulk ship batch successfully closed!'),
            'failedOrdersIds' => $failedOrdersIds,
            'labels' => $labels
        ]);
    }

    /**
     * @param GetShippingRatesRequest $request
     * @param Order $order
     * @return mixed
     */
    public function getShippingRates(GetShippingRatesRequest $request, Order $order): mixed
    {
        $input = $request->validated();

        return app('shipping')->getShippingRates($order, $input);
    }

    /**
     * @param GetShippingRatesRequest $request
     * @param Order $order
     * @return Application|Factory|View
     */
    public function getShippingRatesView(GetShippingRatesRequest $request, Order $order): View|Factory|Application
    {
        $shippingRates = $this->getShippingRates($request, $order);

        return view('packing.shippingRateItems', [
            'shippingRates' => $shippingRates
        ]);
    }

    public function getEDILabels(Request $request, Shipment $shipment): JsonResponse
    {
        try {
            $ediProvider = $this->wholesaleComponent->getProviderForOrder($shipment->order);
            $asn = CrstlASN::where('shipment_id', $shipment->id)->first(); // TODO: Add resiliency.

            if (is_null($asn)) {
                throw new WholesaleException('No ASN was found for the order number ' . $shipment->order->number);
            }

            $asn = $this->wholesaleComponent->getPackingLabels($ediProvider, $asn);

            return response()->json([
                'success' => true,
                // TODO: Where should this serializer actually be?
                'asn' => [
                    'id' => $asn->id,
                    'external_shipment_id' => $asn->external_shipment_id,
                    'shipping_labels_status' => $asn->shipping_labels_status,
                    'asn_status' => $asn->asn_status,
                    'packing_labels' => $asn->packingLabels->map(fn (CrstlPackingLabel $label) => [
                        'signed_url' => $label->signed_url,
                        // TODO: Time zone?
                        'signed_url_expires_at' => $label->signed_url_expires_at->toDateTimeString()
                    ])->toArray()
                ]
            ]);
        } catch (Throwable $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    public function printEDILabels(Request $request, Shipment $shipment): JsonResponse
    {
        try {
            $asn = CrstlASN::where('shipment_id', $shipment->id)->first(); // TODO: Add resiliency.
            $printer = Printer::find($request->get('printer_id'));
            $this->wholesaleComponent->printPackingLabels($asn, $printer);

            return response()->json(['success' => true]);
        } catch (Throwable $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    private function getPackageDocumentsForPrinting($shipment): array
    {
        $printables = [];

        foreach ($shipment->packages as $package) {
            foreach ($package->documents as $packageDocument) {
                if ($packageDocument->print_with_label) {
                    $printables[] = [
                        'url' => route('shipment.package_document', [
                            'shipment' => $shipment,
                            'packageDocument' => $packageDocument
                        ]),
                        'name' => \Str::headline($packageDocument->type),
                        'shipment_id' => $shipment->id
                    ];
                }
            }
        }

        return $printables;
    }

    /**
     * @param GetShippingRatesRequest $request
     * @param Order $order
     * @return mixed
     */
    public function getCheapestShippingRates(GetShippingRatesRequest $request, Order $order): mixed
    {
        $input = $request->validated();

        return app('shipping')->getCheapestShippingRates($order, $input);
    }
}
