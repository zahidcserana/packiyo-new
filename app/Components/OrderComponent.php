<?php

namespace App\Components;

use App\Enums\Source;
use App\Events\OrderCreatedEvent;
use App\Events\OrderUpdatedEvent;
use App\Features\MultiWarehouse;
use App\Features\OrderSearchByNameEmail;
use App\Http\Requests\Csv\{ExportCsvRequest, ImportCsvRequest};
use App\Http\Requests\Order\{BulkSelectionRequest,
    BulkEditRequest,
    DestroyBatchRequest,
    DestroyRequest,
    FilterRequest,
    StoreBatchRequest,
    StoreRequest,
    UpdateBatchRequest,
    UpdateRequest};
use App\Http\Resources\{ExportResources\OrderExportResource, OrderCollection, OrderResource};
use App\Jobs\AllocateInventoryJob;
use App\Jobs\Order\RecalculateReadyToShipOrders;
use App\Models\{Currency,
    Customer,
    CustomerSetting,
    Order,
    OrderChannel,
    OrderItem,
    OrderStatus,
    PrintJob,
    Product,
    PurchaseOrder,
    ReturnItem,
    Warehouse,
    Webhook};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\{JsonResponse, Request, Resources\Json\ResourceCollection};
use Illuminate\Support\{Arr, Collection, Facades\DB, Facades\Log, Facades\Session, Facades\Storage, Str};
use Laravel\Pennant\Feature;
use PDF;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webpatser\Countries\Countries;

class OrderComponent extends BaseComponent
{
    public function store(FormRequest $request, $fireWebhook = true, ?Source $source = null)
    {
        $input = $request->validated();
        $input = $this->updateCurrencyInput($input);
        $input['source'] = $source;

        $shippingInformationData = Arr::get($input, 'shipping_contact_information');

        if (Arr::get($input, 'differentBillingInformation') || !empty(Arr::get($input, 'billing_contact_information'))) {
            $billingInformationData = Arr::get($input, 'billing_contact_information');
        } else {
            $billingInformationData = Arr::get($input, 'shipping_contact_information');
        }

        Arr::forget($input, 'shipping_contact_information');
        Arr::forget($input, 'billing_contact_information');

        if (isset($input['order_status_id']) && $input['order_status_id'] === 'pending') {
            $input['order_status_id'] = null;
        }

        if (!Arr::has($input, 'customer_id')) {
            $input['customer_id'] = Arr::get($input, 'customer.id');
        }

        $orderArr = Arr::except($input, ['order_items', 'tags']);

        if (!isset($orderArr['number'])) {
            $prefix = $input['order_type'] === Order::ORDER_TYPE_TRANSFER ? Order::TRANSFER_ORDER_PREFIX : Order::ORDER_PREFIX;

            $orderArr['number'] = Order::getUniqueIdentifier($prefix, $input['customer_id']);
        }

        if (Arr::get($orderArr, 'shipping_method_id') === 'generic') {
           unset($orderArr['shipping_method_id']);
        }

        $order = Order::create($orderArr);

        Order::disableAuditing();
        OrderItem::disableAuditing();

        if (!isset($input['order_type']) || $input['order_type'] === Order::ORDER_TYPE_REGULAR) {
            $shippingContactInformation = $this->createContactInformation($shippingInformationData, $order);
            $billingContactInformation = $this->createContactInformation($billingInformationData, $order);

            $order->shipping_contact_information_id = $shippingContactInformation->id;
            $order->billing_contact_information_id = $billingContactInformation->id;
        } else {
            $shippingWarehouse = Warehouse::find($input['shipping_warehouse_id']);
            $order->shipping_contact_information_id = $order->billing_contact_information_id = $shippingWarehouse->contactInformation->id;
        }

        $order->save();

        if (!Arr::get($orderArr, 'shipping_method_id') && Arr::get($orderArr, 'shipping_method_name')) {
            app('shippingMethodMapping')->getShippingMethod($order);
        }

        $tags = Arr::get($input, 'tags');
        if (!empty($tags)) {
            $this->updateTags($tags, $order);
        }

        $this->updateOrderItems($order, $input['order_items']);

        event(new OrderCreatedEvent($order, auth()->user(), $source));
        dispatch(new RecalculateReadyToShipOrders([$order->id]));

        $order->getMapCoordinates();

        if (customer_settings($order->customer->id, CustomerSetting::CUSTOMER_SETTING_ORDER_SLIP_AUTO_PRINT)) {
            $defaultSlipPrinter = app('printer')->getDefaultSlipPrinter($order->customer);

            if ($defaultSlipPrinter) {
                PrintJob::create([
                    'object_type' => Order::class,
                    'object_id' => $order->id,
                    'url' => route('order.getOrderSlip', [
                        'order' => $order
                    ]),
                    'printer_id' => $defaultSlipPrinter->id,
                    'user_id' => auth()->user()->id
                ]);
            }
        }

        if (isset($input['order_type']) && $input['order_type'] === Order::ORDER_TYPE_TRANSFER) {
            $this->createTransferOrder($order, $input);
        }

        if ($fireWebhook) {
            $this->webhook(new OrderResource($order), Order::class, Webhook::OPERATION_TYPE_STORE, $order->customer_id, $order->order_channel_id);
        }

        return $order->refresh();
    }

    public function storeBatch(StoreBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $storeRequest = StoreRequest::make($record);
            $responseCollection->add($this->store($storeRequest, false));
        }

        $this->batchWebhook($responseCollection, Order::class, OrderCollection::class, Webhook::OPERATION_TYPE_STORE);

        return $responseCollection;
    }

    public function update(FormRequest $request, Order $order, $fireWebhook = true, ?Source $source = null)
    {
        $input = $request->validated();
        $input = $this->updateCurrencyInput($input);
        $input['source'] = $source;

        if (isset($input['shipping_contact_information'])) {
            $shippingInformationData = Arr::get($input, 'shipping_contact_information');

            if ($order->shippingContactInformation) {
                $order->shippingContactInformation->update($shippingInformationData);
            } else {
                $shippingContactInformation = $this->createContactInformation($shippingInformationData, $order);
                $order->shippingContactInformation()->associate($shippingContactInformation);
            }

            Arr::forget($input, 'shipping_contact_information');
        }

        if (isset($input['billing_contact_information'])) {
            $billingInformationData = Arr::get($input, 'billing_contact_information');

            if ($order->billingContactInformation) {
                $order->billingContactInformation->update($billingInformationData);
            } else {
                $billingContactInformation = $this->createContactInformation($billingInformationData, $order);
                $order->billingContactInformation()->associate($billingContactInformation);
            }

            $order->billingContactInformation->update(Arr::get($input, 'billing_contact_information'));
            Arr::forget($input, 'billing_contact_information');
        }

        if (isset($input['order_items'])) {
            $this->updateOrderItems($order, Arr::get($input, 'order_items'));
        }

        if (Arr::get($input, 'shipping_method_id') === 'generic') {
            Arr::set($input, 'shipping_method_id', null);
        }

        if (isset($input['order_status_id']) && $input['order_status_id'] === 'pending') {
            $input['order_status_id'] = null;
        }

        // We need to convert hold_until, ship_before and scheduled_delivery from user timezone to server timezone
        if ($holdUntil = Arr::get($input, 'hold_until')) {
            $input['hold_until'] = Carbon::parseInUserTimezone($holdUntil)
                ->startOfDay()
                ->toServerTime();
        }

        if ($shipBefore = Arr::get($input, 'ship_before')) {
            $input['ship_before'] = Carbon::parseInUserTimezone($shipBefore)
                ->startOfDay()
                ->toServerTime();
        }

        if ($scheduledDelivery = Arr::get($input, 'scheduled_delivery')) {
            $input['scheduled_delivery'] = Carbon::parseInUserTimezone($scheduledDelivery)
                ->startOfDay()
                ->toServerTime();
        }

        $order->update($input);

        if ($order->wasChanged('allocation_hold')) {
            foreach ($order->orderItems->map->product as $product) {
                if (Feature::for('instance')->active(MultiWarehouse::class)) {
                    AllocateInventoryJob::dispatch($product, $order->warehouse);
                } else {
                    AllocateInventoryJob::dispatch($product);
                }
            }
        }

        if (!Arr::get($input, 'shipping_method_id') && Arr::get($input, 'shipping_method_name')) {
            app('shippingMethodMapping')->getShippingMethod($order);
        }

        if (Arr::exists($input, 'tags')) {
            $this->updateTags(Arr::get($input, 'tags'), $order, true);
        }

        if ($fireWebhook) {
            $this->webhook(new OrderResource($order), Order::class, Webhook::OPERATION_TYPE_UPDATE, $order->customer_id, $order->order_channel_id);
        }

        $this->updateNotes($order, $request);

        $order->getMapCoordinates();

        event(new OrderUpdatedEvent($order, $input, auth()->user(), $source));

        if ($order->wasChanged()) {
            dispatch(new RecalculateReadyToShipOrders([$order->id]));
        }

        return $order->load('orderItems');
    }

    public function updateBatch(UpdateBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $updateRequest = UpdateRequest::make($record);
            $order = Order::find($record['id']);

            $responseCollection->add($this->update($updateRequest, $order, false));
        }

        $this->batchWebhook($responseCollection, Order::class, OrderCollection::class, Webhook::OPERATION_TYPE_UPDATE);

        return $responseCollection;
    }

    public function updateOrderItems(Order $order, array $orderItems): void
    {
        foreach ($orderItems as $item) {
            $orderItem = null;

            if (empty($item['product_id'])) {
                $productQuery = Product::withTrashed()
                    ->where('customer_id', $order['customer_id'])
                    // sort by deleted asc so we get non-deleted products first
                    ->orderBy('deleted_at');

                if (!empty($item['sku'])) {
                    // Getting the product from its SKU.
                    $product = $productQuery->clone()
                        ->where('sku', $item['sku'])
                        ->first();

                    if (!empty($product)) {
                        $item['product_id'] = $product->id;
                    }
                }

                if (empty($item['product_id']) && !empty($item['barcode'])) {
                    // Getting the product from its barcode.
                    $product = $productQuery->clone()
                        ->where('barcode', $item['barcode'])
                        ->first();

                    if (!empty($product)) {
                        $item['product_id'] = $product->id;
                    }
                }
            }

            if (!isset($item['order_item_id'])) {
                $externalId = Arr::get($item, 'external_id');

                if (!empty($externalId)) {
                    if ($orderItemByExternalId = OrderItem::where('external_id', $externalId)->where('order_id', $order->id)->first()) {
                        $item['order_item_id'] = $orderItemByExternalId->id;
                    }
                }
            }

            if (!isset($item['order_item_id'])) {
                $isKitItem = Arr::get($item, 'is_kit_item');
                if ($isKitItem != null && $isKitItem != 'false') {
                    // do not include kit items
                    continue;
                }

                $item['order_id'] = $order->id;
                $orderItem = OrderItem::create($item);
            } else {
                if (isset($item['cancelled']) && $item['cancelled'] == 1) {
                    $item['quantity'] = 0;
                }

                $orderItem = OrderItem::findOrFail($item['order_item_id']);
                $orderItem->update($item);
            }

            if ($orderItem && $orderItem->product && $orderItem->product->isKit()) {
                $this->syncKitsWithOrderItem($orderItem);
            }
        }
    }

    public function destroy(DestroyRequest $request, Order $order, $fireWebhook = true): array
    {
        $order->orderItems()->delete();

        $order->delete();

        $response = ['id' => $order->id, 'customer_id' => $order->customer_id];

        if ($fireWebhook) {
            $this->webhook($response, Order::class, Webhook::OPERATION_TYPE_DESTROY, $order->customer_id, $order->order_chanel_id);
        }

        return $response;
    }

    public function destroyBatch(DestroyBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $destroyRequest = DestroyRequest::make($record);
            $order = Order::find($record['id']);

            $responseCollection->add($this->destroy($destroyRequest, $order, false));
        }

        $this->batchWebhook($responseCollection, Order::class, ResourceCollection::class, Webhook::OPERATION_TYPE_DESTROY);

        return $responseCollection;
    }

    public function generateOrderSlip(Order $order)
    {
        $order->refresh();
        $order = $order->load('orderItems.product', 'orderItems.parentOrderItem');

        $path = 'public/order_slips';
        $pdfName = sprintf("%011d", $order->id) . '_order_slip.pdf';

        if (! Storage::exists($path)) {
            Storage::makeDirectory($path);
        }

        $path .= '/' . $pdfName;

        $paperWidth = paper_width($order->customer_id, 'document');
        $paperHeight = paper_height($order->customer_id, 'document');
        $footerHeight = paper_height($order->customer_id, 'footer');

        PDF::loadView('order_slip.document', [
                'order' => $order,
                'showPricesOnSlip' => customer_settings($order->customer_id, CustomerSetting::CUSTOMER_SETTING_SHOW_PRICES_ON_SLIPS),
                'footerHeight' => $footerHeight,
                'currency' => $order->currency->symbol ?? Currency::find(customer_settings($order->customer_id, CustomerSetting::CUSTOMER_SETTING_CURRENCY))->symbol ?? ''
            ])
            ->setPaper([0, 0, $paperWidth, $paperHeight])
            ->save(Storage::path($path));

        $order->update(['order_slip' => $path]);
    }

    public function getOrderSlip(Order $order)
    {
        $locale = customer_settings($order->customer_id, CustomerSetting::CUSTOMER_SETTING_LOCALE);
        if ($locale) {
            app()->setLocale($locale);
        }

        $this->generateOrderSlip($order);

        return response()->file(Storage::path($order->order_slip), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function filterCustomers(Request $request): JsonResponse
    {
        $term = $request->get('term');
        $results = [];

        if ($term) {
            $contactInformation = Customer::whereHas('contactInformation', static function($query) use ($term) {
                $query->where('name', 'like', $term . '%' )
                    ->orWhere('company_name', 'like',$term . '%')
                    ->orWhere('email', 'like',  $term . '%' )
                    ->orWhere('zip', 'like', $term . '%' )
                    ->orWhere('city', 'like', $term . '%' )
                    ->orWhere('phone', 'like', $term . '%' );
            })->get();

            foreach ($contactInformation as $information) {
                $results[] = [
                    'id' => $information->id,
                    'text' => $information->contactInformation->name
                ];
            }
        }

        return response()->json([
            'results' => $results
        ]);
    }

    /**
     * @param Request $request
     * @param Customer|null $customer
     * @return JsonResponse
     */
    public function filterProducts(Request $request, Customer $customer = null): JsonResponse
    {
        $term = $request->get('term');
        $vendorId = $request->get('vendor_id');
        $results = [];

        if ($term) {
            $term .= '%';

            if (!is_null($customer)) {
                $products = Product::where('customer_id', $customer->id)
                    ->where(static function ($query) use ($term) {
                        return $query->where('sku', 'like', $term)
                            ->orWhere('name', 'like', $term);
                    });

                if (isset($vendorId) && ($vendorId !== 'null' && $vendorId !== 'undefined')) {
                    $products->whereHas('suppliers', function ($q) use ($vendorId) {
                        $q->where('suppliers.id', $vendorId);
                    });
                }

                foreach ($products->get() as $product) {
                    $childProducts = Product::query()
                        ->with('productImages')
                        ->select('*', 'products.*')
                        ->join('kit_items', 'kit_items.child_product_id', '=', 'products.id')
                        ->whereIn('products.id', DB::table('kit_items')->where('parent_product_id', $product->id)->pluck('child_product_id')->toArray())
                        ->where('kit_items.parent_product_id', $product->id)
                        ->groupBy('products.id')->get();

                    $results[] = [
                        'id' => $product->id,
                        'text' => __('SKU: ') . $product->sku . __(', NAME:') . $product->name,
                        'sku' => $product->sku,
                        'name' => $product->name,
                        'image' => $product->productImages[0] ?? null,
                        'price' => $product->price ?? 0,
                        'quantity' => $product->quantity_available ?? 0,
                        'type' => $product->type,
                        'child_products' => $childProducts,
                        'default_image' => asset('img/no-image.png'),
                    ];
                }
            }
        }

        return response()->json([
            'results' => $results
        ]);
    }

    /**
     * @param Request $request
     * @param Customer $customer
     * @return JsonResponse
     */
    public function getOrderStatus(Request $request, Customer $customer): JsonResponse
    {
        $results = [];

        $orderStatuses = OrderStatus::where('customer_id', $customer->id)->get();

        foreach ($orderStatuses as $orderStatus) {
            $results[] = [
                'id' => $orderStatus->id,
                'text' => $orderStatus->name
            ];
        }

        return response()->json([
            'results' => $results
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getBulkOrderStatus(Request $request): JsonResponse
    {
        $fulfilled = true;
        $pending = true;
        $unfulfilled = true;
        $archived = true;
        $unarchived = true;

        $orders = Order::whereIn('id', $request->get('ids', []))->get();

        foreach ($orders as $order) {
            if ($order->fulfilled_at) {
                $pending = false;
                $unfulfilled = false;
            } else {
                $fulfilled = false;
            }

            if ($order->cancelled_at) {
                $pending = false;
            }

            if ($order->is_archived) {
                $unarchived = false;
            } else {
                $archived = false;
            }
        }

        return response()->json([
            'results' => compact('pending', 'fulfilled', 'unfulfilled', 'archived', 'unarchived')
        ]);
    }

    public function filter(FilterRequest $request, $customerIds)
    {
        $query = Order::query();

        $query->when($request['from_date_created'], function ($q) use($request){
            return $q->where('created_at', '>=', $request['from_date_created']);
        });

        $query->when($request['to_date_created'], function ($q) use($request){
            return $q->where('created_at', '<=', $request['to_date_created'].' 23:59:59');
        });

        $query->when($request['from_date_updated'], function ($q) use($request){
            return $q->where('updated_at', '>=', $request['from_date_updated']);
        });

        $query->when($request['to_date_updated'], function ($q) use($request){
            return $q->where('updated_at', '<=', $request['to_date_updated'].' 23:59:59');
        });

        $query->when($request['ready_to_ship'], function ($q) use($request){
            return $q->where('ready_to_ship', $request['ready_to_ship']);
        });

        $query->when(count($customerIds) > 0, function ($q) use($customerIds){
            return $q->whereIn('customer_id', $customerIds);
        });

        return $query->paginate();
    }

    public function updatePriorityScore(Order $order)
    {
        if (!$order->fulfilled_at && !$order->cancelled_at) {
            $order->priority_score = now()->diffInDays($order->ordered_at) * 20 + intval($order->priority) * 100;
        }

        return $order;
    }

    public function search($term, $setDefaultDate = true)
    {
        $customer = app('user')->getSelectedCustomers();

        if (!auth()->user()->isAdmin()) {
            $customers = $customer->pluck('id')->toArray();

            $orderQuery = Order::whereIn('customer_id', $customers);
        } else {
            $customerIds = Auth()->user()->customerIds();
            $orderQuery = Order::whereIn('orders.customer_id', $customerIds);
        }

        return $orderQuery;
    }

    /**
     * @param Order $order
     * @return Order
     */
    public function cancelOrder(Order $order, $fireWebhook = true): Order
    {
        if (!$order->fulfilled_at && !$order->cancelled_at) {
            $order->cancelled_at = Carbon::now();
            $order->ready_to_ship = false;
            $order->ready_to_pick = false;
            // TODO: saveQuietly requires us to do this. Rethink.
            $order->batch_key = null;

            $order->saveQuietly();

            foreach ($order->orderItems as $orderItem) {
                $this->cancelOrderItem($orderItem);
            }

            Order::auditCustomEvent($order, 'cancelled', __('Order was cancelled'));
        }

        if ($fireWebhook) {
            $this->webhook(new OrderResource($order), Order::class, Webhook::OPERATION_TYPE_DESTROY, $order->customer_id, $order->order_chanel_id);
        }

        return $order;
    }

    /**
     * @param OrderItem $orderItem
     * @param bool $triggerAudit
     * @return Order
     */
    public function cancelOrderItem(OrderItem $orderItem, bool $triggerAudit = false): Order
    {
        if ($orderItem->quantity_pending > 0) {
            $orderItem->quantity_pending = 0;
            $orderItem->quantity_allocated = 0;
            $orderItem->quantity_allocated_pickable = 0;
            $orderItem->cancelled_at = Carbon::now();

            $orderItem->save();
        }

        foreach ($orderItem->kitOrderItems as $kitOrderItem) {
            if ($kitOrderItem->quantity_pending > 0) {
                $kitOrderItem->quantity_pending = 0;
                $kitOrderItem->quantity_allocated = 0;
                $kitOrderItem->quantity_allocated_pickable = 0;
                $kitOrderItem->cancelled_at = Carbon::now();

                $kitOrderItem->save();
            }
        }

        if ($orderItem->parentOrderItem) {
            if ($orderItem->parentOrderItem->kitOrderItems()->sum('quantity_pending') == 0) {
                $this->cancelOrderItem($orderItem->parentOrderItem);
            }
        }

        if ($triggerAudit) {
            OrderItem::auditCustomEvent(
                $orderItem,
                'cancelled',
                __(Arr::get(OrderItem::$eventMessage, 'cancelled'), ['sku' => $orderItem->sku, 'name' => $orderItem->name])
            );
        }

        return $orderItem->order;
    }

    /**
     * @param Order $order
     * @return Order
     */
    public function uncancelOrder(Order $order): Order
    {
        $order->cancelled_at = null;

        $order->saveQuietly();

        foreach ($order->orderItems as $orderItem) {
            $this->uncancelOrderItem($orderItem);
        }

        Order::auditCustomEvent($order, 'uncancelled', __('Order was uncancelled'));

        return $order;
    }

    /**
     * @param OrderItem $orderItem
     * @param bool $triggerAudit
     * @return Order
     */
    public function uncancelOrderItem(OrderItem $orderItem, bool $triggerAudit = false): Order
    {
        $orderItem->cancelled_at = null;

        $orderItem->save();

        foreach ($orderItem->kitOrderItems as $kitOrderItem) {
            $kitOrderItem->cancelled_at = null;

            $kitOrderItem->save();
        }

        if ($triggerAudit) {
            OrderItem::auditCustomEvent(
                $orderItem,
                'uncancelled',
                __(Arr::get(OrderItem::$eventMessage, 'uncancelled'), ['sku' => $orderItem->sku, 'name' => $orderItem->name])
            );
        }

        return $orderItem->order;
    }

    /**
     * @param Order $order
     * @return Order
     */
    public function markAsFulfilled(Order $order): Order
    {
        if (is_null($order->fulfilled_at)) {
            $order->fulfilled_at = Carbon::now();
            $order->ready_to_ship = false;
            $order->ready_to_pick = false;
            // TODO: saveQuietly requires us to do this. Rethink.
            $order->batch_key = null;

            $order->saveQuietly();

            foreach ($order->orderItems as $orderItem) {
                $orderItem->quantity_pending = 0;
                $orderItem->quantity_allocated = 0;
                $orderItem->quantity_allocated_pickable = 0;
                $orderItem->saveQuietly();

                if ($orderItem->product) {
                    if (Feature::for('instance')->active(MultiWarehouse::class)) {
                        AllocateInventoryJob::dispatch($orderItem->product, $order->warehouse);
                    } else {
                        AllocateInventoryJob::dispatch($orderItem->product);
                    }
                }
            }

            Order::auditCustomEvent($order, 'fulfilled', __('Order was fulfilled'));
        }

        return $order;
    }

    /**
     * @param Order $order
     * @return Order
     */
    public function markAsUnfulfilled(Order $order): Order
    {
        if(!is_null($order->fulfilled_at)) {
            $order->fulfilled_at = null;
            $order->saveQuietly();

            foreach ($order->orderItems as $orderItem) {
                $orderItem->touch();

                if ($orderItem->product) {
                    AllocateInventoryJob::dispatch($orderItem->product);
                }
            }

            Order::auditCustomEvent($order, 'unfulfilled', __('Order was unfulfilled'));
        }
        return $order;
    }

    /**
     * @param Order $order
     * @return Order
     */
    public function archiveOrder(Order $order): Order
    {
        if (is_null($order->archived_at)) {
            $order->archived_at = Carbon::now();
            $order->ready_to_ship = false;
            $order->ready_to_pick = false;

            $order->saveQuietly();

            foreach ($order->orderItems as $orderItem) {
                $orderItem->quantity_pending = 0;
                $orderItem->quantity_allocated = 0;
                $orderItem->quantity_allocated_pickable = 0;
                $orderItem->saveQuietly();

                if ($orderItem->product) {
                    if (Feature::for('instance')->active(MultiWarehouse::class)) {
                        AllocateInventoryJob::dispatch($orderItem->product, $order->warehouse);
                    } else {
                        AllocateInventoryJob::dispatch($orderItem->product);
                    }
                }
            }

            Order::auditCustomEvent($order, 'archived', __('Order was archived'));
        }

        return $order;
    }

    /**
     * @param Order $order
     * @return Order
     */
    public function unarchiveOrder(Order $order): Order
    {
        if ($order->is_archived) {
            $order->archived_at = null;
            $order->saveQuietly();

            foreach ($order->orderItems as $orderItem) {
                $orderItem->touch();
            }

            Order::auditCustomEvent($order, 'unarchived', __('Order was unarchived'));
        }

        return $order;
    }

    /**
     * @param Order $order
     * @return Order
     */
    public function unlockOrder(Order $order): Order
    {
        while ($orderLock = $order->orderLock()->first()) {
            $orderLock->delete();

            Order::auditCustomEvent($order,
                'unlocked',
                __('Order lock placed by :user has been removed', [
                    'user' => $orderLock->user->contactInformation->name ?? ''
                ])
            );
        }

        return $order;
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function isOrderPartiallyShipped(Order $order): bool
    {
        foreach ($order->orderItems as $orderItem) {
            if ($orderItem->quantity_shipped > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Order $order
     * @return void
     */
    public function recalculateStatus(Order $order): void
    {
        $hasQuantityPending = false;
        $hasQuantityShipped = false;
        foreach ($order->orderItems as $orderItem) {
            if ($orderItem->quantity_pending > 0) {
                $hasQuantityPending = true;
            }

            if ($orderItem->quantity_shipped > 0) {
                $hasQuantityShipped = true;
            }
        }

        if (!$hasQuantityPending && $hasQuantityShipped) {
            if (!$order->fulfilled_at) {
                $order->fulfilled_at = Carbon::now();
            }
        } else {
            $order->fulfilled_at = null;
        }

        if (!$hasQuantityPending && !$hasQuantityShipped && !$order->fulfilled_at && !$order->archived_at) {
            if (!$order->cancelled_at) {
                $order->cancelled_at = Carbon::now();
            }
        } else {
            $order->cancelled_at = null;
        }

        if (is_null($order->ordered_at)) {
            $order->ordered_at = Carbon::now();
        }
    }

    /**
     * @param Order $order
     * @return void
     */
    public function recalculateTotals(Order $order): void
    {
        $subtotal = 0;

        foreach ($order->orderItems as $orderItem) {
            $price = (float) $orderItem->price * $orderItem->quantity;
            $subtotal += $price;
        }

        $order->subtotal = $subtotal;

        $order->total = $order->subtotal + $order->tax + $order->shipping;
    }

    /**
     * @param int[]|null $orderIds
     * @return void
     * @throws \Exception
     */
    public function recalculateReadyToShipOrders(array $orderIds = null): void
    {
        $query = Order::query();

        if (!empty($orderIds)) {
            $query->whereIntegerInRaw('id', $orderIds);
        } else {
            $query->where(static function (Builder $query) {
                return $query->where(static function (Builder $query) {
                    return $query->whereNull('fulfilled_at')
                        ->whereNull('cancelled_at')
                        ->whereNull('archived_at');
                })
                    ->orWhere('ready_to_ship', 1)
                    ->orWhere('ready_to_pick', 1);
            });
        }

        $query->eachById(static function (Order $order) {
            $readyToShip = 1;

            // TODO check this, I think we should be taking the user/warehouse timezone into account
            if ($order->hold_until != null && $order->hold_until->greaterThan(now())) {
                $readyToShip = 0;
            } else if ($order->has_holds) {
                $readyToShip = 0;
            } else if ($order->fulfilled_at != null) {
                $readyToShip = 0;
            } else if ($order->cancelled_at != null) {
                $readyToShip = 0;
            } else if ($order->archived_at != null) {
                $readyToShip = 0;
            } else if ($order->quantity_allocated_sum <= 0) {
                $readyToShip = 0;
            } else if ($order->quantity_pending_sum > $order->quantity_allocated_sum + $order->quantity_backordered_sum) {
                // not all lines went through allocation
                $readyToShip = 0;
            } else if ($order->quantity_allocated_sum < $order->quantity_pending_sum && $order->allow_partial == 0) {
                $readyToShip = 0;
            }

            $readyToPick = $readyToShip;

            if ($order->quantity_allocated_pickable_sum <= 0) {
                $readyToPick = 0;
            } else if ($order->quantity_allocated_pickable_sum < $order->quantity_pending_sum && $order->allow_partial == 0) {
                $readyToPick = 0;
            }

            $order->ready_to_ship = $readyToShip;
            $order->ready_to_pick = $readyToPick;

            $order->regenerateBatchKey();

            $order->saveQuietly();
        });
    }

    /**
     * @param ImportCsvRequest $request
     * @return string
     */
    public function importCsv(ImportCsvRequest $request): string
    {
        $input = $request->validated();

        $importLines = app('csv')->getCsvData($input['import_csv']);

        $columns = array_intersect(
            app('csv')->unsetCsvHeader($importLines, 'shipping_contact_information_name'),
            OrderExportResource::columns()
        );

        if (!empty($importLines)) {
            $storedCollection = new Collection();
            $updatedCollection = new Collection();

            $ordersToImport = [];

            foreach ($importLines as $importLine) {
                $data = [];
                $data['customer_id'] = $input['customer_id'];

                foreach ($columns as $columnsIndex => $column) {
                    if (Arr::has($importLine, $columnsIndex)) {
                        $data[$column] = Arr::get($importLine, $columnsIndex);
                    }
                }

                if (!Arr::has($ordersToImport, $data['order_number'])) {
                    $ordersToImport[$data['order_number']] = [];
                }

                $ordersToImport[$data['order_number']][] = $data;
            }

            $orderToImportIndex = 0;

            foreach ($ordersToImport as $number => $orderToImport) {
                $order = Order::where('customer_id', $orderToImport[0]['customer_id'])->where('number', $number)->first();

                DB::transaction(function() use ($orderToImport, $storedCollection, $updatedCollection, $order) {
                    if ($order) {
                        $updatedCollection->add($this->update(
                            $this->createRequestFromImport($orderToImport, $order, true),
                            $order,
                            false,
                            Source::MANUAL_VIA_FILE_UPLOAD
                        ));
                    } else {
                        $storedCollection->add($this->store(
                                $this->createRequestFromImport($orderToImport, $order),
                                source: Source::MANUAL_VIA_FILE_UPLOAD
                        ));
                    }
                }, 10);

                Session::flash('status', ['type' => 'info', 'message' => __('Importing :current/:total orders', ['current' => ++$orderToImportIndex , 'total' => count($ordersToImport)])]);
                Session::save();
            }

            $this->batchWebhook($storedCollection, Order::class, OrderCollection::class, Webhook::OPERATION_TYPE_STORE);
            $this->batchWebhook($updatedCollection, Order::class, OrderCollection::class, Webhook::OPERATION_TYPE_UPDATE);
        }

        Session::flash('status', ['type' => 'success', 'message' => __('Orders were successfully imported!')]);

        return __('Orders were successfully imported!');
    }

    /**
     * @param ExportCsvRequest $request
     * @return StreamedResponse
     */
    public function exportCsv(ExportCsvRequest $request): StreamedResponse
    {
        $input = $request->validated();
        $search = $input['search']['value'];

        if ($search) {
            $orders = $this->getQuery();

            $orders = $this->searchQuery($search, $orders);
        } else {
            $orders = $this->getQuery($request->get('filter_form'));
        }

        $csvFileName = Str::kebab(auth()->user()->contactInformation->name) . '-orders-export.csv';

        return app('csv')->export($request, $orders->get(), OrderExportResource::columns(), $csvFileName, OrderExportResource::class);
    }

    /**
     * @param array $data
     * @param Order|null $order
     * @param bool $update
     * @return StoreRequest|UpdateRequest
     */
    private function createRequestFromImport(array $data, Order $order = null, bool $update = false)
    {
        $customerId = $data[0]['customer_id'];

        $orderStatus = OrderStatus::where('customer_id', Arr::get($data[0], 'customer_id'))->where('name', Arr::get($data[0], 'status'))->first();

        $shippingCountry = Countries::find(Arr::get($data[0], 'shipping_contact_information_country'));

        if (!$shippingCountry) {
            $shippingCountry = Countries::where('iso_3166_2', Arr::get($data[0], 'shipping_contact_information_country'))->first();
        }

        $orderChannel = OrderChannel::find(Arr::get($data[0], 'order_channel'));

        if (!$orderChannel) {
            $orderChannel = OrderChannel::where('name', Arr::get($data[0], 'order_channel'))->first();
        }

        $requestData = [
            'customer_id' => Arr::get($data[0], 'customer_id'),
            'number' => Arr::get($data[0], 'order_number'),
            'order_channel_id' => $orderChannel->id ?? null,
            'order_status_id' => $orderStatus->id ?? 'pending',
            'shipping_method_name' => Arr::get($data[0], 'shipping_method_name', 'generic'),
            'shipping_contact_information' => [
                'name' => Arr::get($data[0], 'shipping_contact_information_name'),
                'company_name' => Arr::get($data[0], 'shipping_contact_information_company_name'),
                'company_number' => Arr::get($data[0], 'shipping_contact_information_company_number'),
                'address' => Arr::get($data[0], 'shipping_contact_information_address'),
                'address2' => Arr::get($data[0], 'shipping_contact_information_address2'),
                'zip' => Arr::get($data[0], 'shipping_contact_information_zip'),
                'city' => Arr::get($data[0], 'shipping_contact_information_city'),
                'state' => Arr::get($data[0], 'shipping_contact_information_state'),
                'country_id' => $shippingCountry->id ?? null,
                'phone' => Arr::get($data[0], 'shipping_contact_information_phone'),
                'email' => Arr::get($data[0], 'shipping_contact_information_email')
            ],
            'priority' => strtolower(Arr::get($data[0], 'priority')) == 'yes' ? 1 : 0,
            'tags' => explode(',', Arr::get($data[0], 'tags'))
        ];

        $billingCountry = Countries::find(Arr::get($data[0], 'billing_contact_information_country'));

        if (!$billingCountry) {
            $billingCountry = Countries::where('iso_3166_2', Arr::get($data[0], 'billing_contact_information_country'))->first();
        }

        if ($billingCountry) {
            $requestData['billing_contact_information'] = [
                'name' => Arr::get($data[0], 'billing_contact_information_name'),
                'company_name' => Arr::get($data[0], 'billing_contact_information_company_name'),
                'company_number' => Arr::get($data[0], 'billing_contact_information_company_number'),
                'address' => Arr::get($data[0], 'billing_contact_information_address'),
                'address2' => Arr::get($data[0], 'billing_contact_information_address2'),
                'zip' => Arr::get($data[0], 'billing_contact_information_zip'),
                'city' => Arr::get($data[0], 'billing_contact_information_city'),
                'state' => Arr::get($data[0], 'billing_contact_information_state'),
                'country_id' => $billingCountry->id,
                'phone' => Arr::get($data[0], 'billing_contact_information_phone'),
                'email' => Arr::get($data[0], 'billing_contact_information_email')
            ];
        } else {
            $requestData['billing_contact_information'] = $requestData['shipping_contact_information'];
        }

        foreach ($data as $key => $line) {

            $sku = trim($line['sku']);

            $product = Product::withTrashed()->where('customer_id', $data[0]['customer_id'])
                ->where('sku', $sku)
                ->first();

            $requestData['order_items'][$key] = [
                'product_id' => $product->id ?? null,
                'sku' => $sku,
                'quantity' => $line['quantity'],
            ];

            if ($update) {
                $orderItem = OrderItem::where('order_id', $order->id)
                    ->where('product_id', $requestData['order_items'][$key]['product_id'])
                    ->first();

                if (!is_null($orderItem)) {
                    $requestData['order_items'][$key]['order_item_id'] = $orderItem->id;
                }
            }
        }

        $warehouseName = Arr::get($data[0], 'warehouse');

        if ($warehouseName) {
            $customer = Customer::find($customerId);
            $customerIds = [$customer->id];

            if ($customer->parent_id) {
                $customerIds[] = $customer->parent_id;
            }

            $warehouse = Warehouse::whereIn('customer_id', $customerIds)
                ->whereHas('contactInformation', static function($query) use ($warehouseName) {
                    $query->where('name', $warehouseName);
                })
                ->first();

            if ($warehouse) {
                $requestData['warehouse_id'] = $warehouse->id;
            }
        }

        return $update ? UpdateRequest::make($requestData) : StoreRequest::make($requestData);
    }

    public function getFilterInputSkusInfo($filterInputs) {
        $skus = explode(',', Arr::get($filterInputs, 'skus'));
        $hasStar = $skus[count($skus) - 1] == '*';

        if ($hasStar) {
            unset($skus[count($skus) - 1]);
        }

        $skus = array_map('trim', $skus);
        sort($skus);

        $positiveSkus = [];
        $negativeSkus = [];

        foreach ($skus as $sku) {
            if (!str_starts_with($sku, '-')) {
                $positiveSkus[] = $sku;
            } else {
                $negativeSkus[] = substr($sku, 1);
            }
        }

        if (!$hasStar) {
            $filterInputs['skus_info']['concated_skus'] = '"'. implode(',', $positiveSkus) . '"';
        }

        $filterInputs['skus_info']['has_star'] = $hasStar;
        $filterInputs['skus_info']['positive_skus'] = $positiveSkus;
        $filterInputs['skus_info']['negative_skus'] = $negativeSkus;

        return $filterInputs;
    }

    /**
     * @param array $filterInputs
     * @param string $sortColumnName
     * @return Builder
     */
    public function getQuery(array $filterInputs = [], string $sortColumnName = 'ordered_at'): Builder
    {
        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        if (Arr::get($filterInputs, 'skus')) {
           $filterInputs = $this->getFilterInputSkusInfo($filterInputs);
        }

        $filterCustomerId = Arr::get($filterInputs, 'customer_id');

        if ($filterCustomerId && $filterCustomerId != 'all') {
            $customerIds = array_intersect($customerIds, [$filterCustomerId]);
        }

        $orderCollection = Order::with([
            'customer.contactInformation',
            'shippingContactInformation.country',
            'orderStatus',
            'orderItems.placedToteOrderItems.tote',
            'tags',
            'warehouse.contactInformation',
            'shippingMethod',
            'orderLock'
        ])
        ->when(Arr::get($filterInputs, 'skus'), function ($q) {
            return $q->join('order_items', 'orders.id', '=', 'order_items.order_id')
                     ->join('products', 'order_items.product_id', '=', 'products.id');
        })
        ->when((Arr::get($filterInputs, 'term') && Feature::for('instance')->active(OrderSearchByNameEmail::class)) || Arr::get($filterInputs, 'country'), function ($q) {
            return $q->join('contact_informations', 'contact_informations.id', '=', 'orders.shipping_contact_information_id');
        })
        ->whereIn('orders.customer_id', $customerIds)
        ->when(!empty($filterInputs), static function (Builder $query) use ($sortColumnName, $filterInputs) {

            // Find by filter result
            // Start/End date
            if (Arr::get($filterInputs, 'start_date') || Arr::get($filterInputs, 'end_date')) {
                $startDate = Carbon::parseInUserTimezone($filterInputs['start_date'] ?? '1970-01-01')
                    ->startOfDay()
                    ->toServerTime();
                $endDate = Carbon::parseInUserTimezone($filterInputs['end_date'] ?? Carbon::now())
                    ->endOfDay()
                    ->toServerTime();

                $query->whereBetween('orders.ordered_at', [$startDate, $endDate]);
            }

            // Order Status
            if (Arr::get($filterInputs, 'order_status')) {
                if ($filterInputs['order_status'] === 'fulfilled') {
                    $query->whereNotNull('orders.fulfilled_at');
                } else if ($filterInputs['order_status'] === 'cancelled') {
                    $query->whereNotNull('orders.cancelled_at');
                } else if ($filterInputs['order_status'] === 'pending') {
                    $query
                        ->whereNull([
                            'orders.cancelled_at',
                            'orders.fulfilled_at',
                            'orders.order_status_id'
                        ]);
                } else {
                    $query
                        ->where('orders.order_status_id', (int)$filterInputs['order_status'])
                        ->whereNull([
                            'orders.cancelled_at',
                            'orders.fulfilled_at'
                        ]);
                }
            }

            if (isset($filterInputs['in_tote'])) {
                if ($filterInputs['in_tote'] == 1) {
                    $query->has('orderItems.placedToteOrderItems');
                } else {
                    $query->doesntHave('orderItems.placedToteOrderItems');
                }
            }

            // Ready To Ship
            if (($filterInputs['ready_to_ship'] ?? 'all') !== 'all') {
                $query->where('orders.ready_to_ship', (int) $filterInputs['ready_to_ship']);
            }

            if (($filterInputs['ready_to_pick'] ?? 'all') !== 'all') {
                $query->where('orders.ready_to_pick', (int) $filterInputs['ready_to_pick']);
            }

            // Priority
            if (Arr::get($filterInputs, 'priority')) {
                $query->where('orders.priority', $filterInputs['priority']);
            }

            if (Arr::get($filterInputs, 'disabled_on_picking_app')) {
                $query->where('orders.disabled_on_picking_app', $filterInputs['disabled_on_picking_app']);
            }

            // SKUs
            if (Arr::get($filterInputs, 'skus') && $filterInputs['skus_info']['has_star']) {
                if (!empty($filterInputs['skus_info']['positive_skus'])) {
                    $query->whereIn('products.sku', $filterInputs['skus_info']['positive_skus']);
                }

                if (!empty($filterInputs['skus_info']['negative_skus'])) {
                    $query->whereNotIn('products.sku', $filterInputs['skus_info']['negative_skus']);
                }
            }

            // Backorder
            if (isset($filterInputs['backordered'])) {
                if ((int)$filterInputs['backordered'] === 0) {
                    $query->whereHas('orderItems', function ($q) {
                        return $q->where('quantity_backordered', '>', 0);
                    });
                } else if ((int)$filterInputs['backordered'] === 1) {
                    $query->whereDoesntHave('orderItems', function ($q) {
                        return $q->where('quantity_backordered', '>', 0);
                    });
                }
            }

            // Locked
            if (is_numeric(Arr::get($filterInputs, 'locked'))) {
                if ((int)$filterInputs['locked'] === 0) {
                    $query->whereDoesntHave('orderLock');
                } else if ((int)$filterInputs['locked'] === 1) {
                    $query->whereHas('orderLock');
                }
            }

            // Archived
            if (is_numeric(Arr::get($filterInputs, 'archived'))) {
                if ((int)$filterInputs['archived'] === 0) {
                    $query->whereNotNull('archived_at');
                } else if ((int)$filterInputs['archived'] === 1) {
                    $query->whereNull('archived_at');
                }
            }

            // Transfer order
            if (is_numeric(Arr::get($filterInputs, 'transfer_order'))) {
                if ((int)$filterInputs['transfer_order'] === 0) {
                    $query->whereHas('purchaseOrder');
                } else if ((int)$filterInputs['transfer_order'] === 1) {
                    $query->WhereDoesntHave('purchaseOrder');
                }
            }

            // Weight
            if (Arr::get($filterInputs, 'weight_to') || Arr::get($filterInputs, 'weight_from')) {
                $from = $filterInputs['weight_from'] ?? 0;
                $to = $filterInputs['weight_to'] ?? Order::query()->max('weight');

                $query->whereBetween('orders.weight', [(int)$from, (int)$to]);
            }

            // Any hold
            if (Arr::exists($filterInputs, 'any_hold') && $filterInputs['any_hold'] !== null) {
                match ($filterInputs['any_hold']) {
                    'any_hold' => $query->where(function($q) {
                        $q->where('fraud_hold', 1)
                            ->orWhere('address_hold', 1)
                            ->orWhere('payment_hold', 1)
                            ->orWhere('operator_hold', 1)
                            ->orWhere('allocation_hold', 1)
                            ->orWhere('hold_until', '>', now()->toDateString());
                    }),
                    'fraud_hold' => $query->where('fraud_hold', 1),
                    'address_hold' => $query->where('address_hold', 1),
                    'payment_hold' => $query->where('payment_hold', 1),
                    'operator_hold' => $query->where('operator_hold', 1),
                    'allocation_hold' => $query->where('allocation_hold', 1),
                    'hold_until' => $query->where('hold_until', '>', now()->toDateString()),
                    'none' => $query->where(function($q) {
                        $q->where('fraud_hold', 0)
                            ->where('address_hold', 0)
                            ->where('payment_hold', 0)
                            ->where('operator_hold', 0)
                            ->where('allocation_hold', 0)
                            ->where(function($q) {
                                $q->whereDate('hold_until', '<=', now()->toDateString())
                                ->orWhereNull('hold_until');
                            });
                    }),
                    default => $query,
                };
            }

            // Tags
            if (Arr::exists($filterInputs, 'tags') && Arr::get($filterInputs, 'tags')) {
                $filterTags = (array) $filterInputs['tags'];
                $query->whereHas('tags', function($query) use ($filterTags) {
                    $query->whereIn('name', $filterTags);
                });
            }

            // Required ship date
            if (Arr::get($filterInputs, 'ship_before')) {
                $shipBeforeDate = Carbon::parseInUserTimezone($filterInputs['ship_before'])
                    ->startOfDay()
                    ->toServerTime();
                $query->where('orders.ship_before', '=', $shipBeforeDate);
            }

            // Transfer order
            if (is_numeric(Arr::get($filterInputs, 'warehouse_id'))) {
                $query->where('warehouse_id', $filterInputs['warehouse_id']);
            }
        })
        ->select('orders.*')
        ->groupBy('orders.id')
        ->when(Arr::get($filterInputs, 'skus_info.concated_skus'), function ($q) use ($filterInputs) {
            $q->havingRaw('GROUP_CONCAT(order_items.sku order by order_items.sku asc) = ' . $filterInputs['skus_info']['concated_skus']);
        });

        // Country
        if (Arr::get($filterInputs, 'country')) {
            $orderCollection->where('contact_informations.country_id', $filterInputs['country']);
        }

        // Shipping Method
        if (Arr::get($filterInputs, 'shipping_method') || Str::contains($sortColumnName, 'shipping_methods')) {
            $orderCollection->leftJoin('shipping_methods', 'shipping_methods.id', '=', 'orders.shipping_method_id');

            if (Arr::get($filterInputs, 'shipping_method')) {
                $orderCollection->where('shipping_methods.name', $filterInputs['shipping_method']);
            }
        }

        // Carriers
        if (Arr::get($filterInputs, 'shipping_carrier')) {
            if (!collect($orderCollection->getQuery()->joins)->pluck('table')->contains('shipping_methods')) {
                $orderCollection->join('shipping_methods', 'shipping_methods.id', '=', 'orders.shipping_method_id');
            }

            $orderCollection->join('shipping_carriers', 'shipping_carriers.id', '=', 'shipping_methods.shipping_carrier_id');
            $orderCollection->where('shipping_carriers.name', $filterInputs['shipping_carrier']);
        }

        // Automation
        if (Arr::exists($filterInputs, 'automation') && $filterInputs['automation'] !== '0') {
            $orderCollection
                ->join('automation_acted_on_operation', function (JoinClause $join) {
                    $join->on('automation_acted_on_operation.operation_id', '=', 'orders.id')
                        ->where('automation_acted_on_operation.operation_type', Order::class);
                })
                ->join('automations', 'automations.id', '=', 'automation_acted_on_operation.automation_id');
            $orderCollection->where('automations.name', $filterInputs['automation']);
        }

        return $orderCollection;
    }

    /**
     * @param string $search
     * @param $orders
     * @return mixed
     */
    public function searchQuery(string $search, $orders)
    {
        $term = $search . '%';

        $orders->where(function (Builder $query) use ($term) {
            $query->where('number', 'like', $term);

            if (Feature::for('instance')->active(OrderSearchByNameEmail::class)) {
                $query->orWhere('name', 'like', $term)->orWhere('email', 'like', $term);
            }
        });

        return $orders;
    }

    public function setOperatorHold(Order $order)
    {
        $order->update(['operator_hold' => 1]);
    }

    public function updateOrderStatus(Order $order, $orderStatusId)
    {
        $order->update(['order_status_id' => $orderStatusId !== 'pending' ? $orderStatusId : null]);
    }

    public function reshipOrderItems(Order $order, $request)
    {
        $reshippedItemsNum = 0;

        foreach ($request->order_items as $data) {
            if (isset($data['order_item_id'])) {
                $reshippedItemsNum++;
                $orderItemId = $data['order_item_id'];
                $quantityReship = $data['quantity'];
                $addInventory = isset($data['add_inventory']);

                $orderItem = OrderItem::findOrFail($orderItemId);

                $this->reshipItem($orderItem, $quantityReship, $addInventory);

                foreach ($orderItem->kitOrderItems as $orderItemComponent) {
                    $componentQuantity = $orderItem->product->kitItems
                        ->where('pivot.child_product_id', $orderItemComponent->product_id)
                        ->first()
                        ->pivot->quantity ?? 0;

                    $this->reshipItem($orderItemComponent, $quantityReship * $componentQuantity, $addInventory);
                }
            }
        }

        if ($reshippedItemsNum > 0) {
            if ($request->operator_hold) {
                app('order')->setOperatorHold($order);
            }
            if ($request->reship_order_status_id > 0) {
                app('order')->updateOrderStatus($order, $request->reship_order_status_id);
            }

            $order->update();

            Order::auditCustomEvent($order, 'reshipped', __('Order reshipped'));
        }

        return $reshippedItemsNum;
    }

    public function reshipItem(OrderItem $orderItem, $reshipQuantity, $addInventory)
    {
        if ($orderItem->kitOrderItems->count() > 0) {
            $addInventory = false;
        }

        $orderItem->increment('quantity_reshipped', $reshipQuantity);
        $orderItem->increment('quantity_pending', $reshipQuantity);
        $orderItem->save();

        if ($addInventory && $orderItem->packageOrderItems->last()->location) {
            $warehouse = $orderItem->packageOrderItems->last()->location->warehouse;
            if ($warehouse) {
                $location = $warehouse->reshipLocation();
                if ($location) {
                    app('inventoryLog')->adjustInventory($location, $orderItem->product, $reshipQuantity, InventoryLogComponent::OPERATION_TYPE_RESHIP);
                }
            }
        }
    }

    public function getShippedOrderItems(Order $order)
    {
        $orderId = $order->id;

        $orderItems = OrderItem::where('order_id', $orderId)
            ->where('quantity_shipped', '>', 0)
            ->whereHas('product', function ($query) {
                $query->where('type', Product::PRODUCT_TYPE_REGULAR);
            })
            ->get();

        $orderReturnsIds = Order::find($orderId)
            ->returns()
            // TODO Check if condition should be added
            // ->where('approved', true)
            ->pluck('id')
            ->toArray();

        $returnItems = ReturnItem::whereIn('return_id', $orderReturnsIds)
            ->whereIn('order_item_id', $orderItems->pluck('id')->toArray())
            ->get()
            ->groupBy('order_item_id')
            ->map(function ($items) {
                return $items->sum('quantity');
            })
            ->toArray();

        $results = [];

        foreach ($orderItems as $orderItem) {
            $quantity = $orderItem->quantity_shipped;

            if (isset($returnItems[$orderItem->id])) {
                $quantity -= $returnItems[$orderItem->id];
            }

            if ((int)$quantity > 0) {
                $results[] = [
                    'id' => $orderItem->product_id,
                    'location_id' => $orderItem->product->locations->first() ? $orderItem->product->locations->first()->id : '',
                    'order_item_id' => $orderItem->id,
                    'image' => $orderItem->product->productImages->first()->source ?? asset('img/inventory.svg'),
                    'text' => __('NAME: ') . $orderItem->product->name . '<br>' . __('SKU: ') . $orderItem->product->sku,
                    'quantity' => $quantity
                ];
            }
        }

        return $results;
    }

    /**
     * @param BulkEditRequest $request
     * @return void
     */
    public function bulkEdit(BulkEditRequest $request): void
    {
        $input = $request->validated();
        $orderIds = explode(',', Arr::get($input, 'ids'));
        $updateColumns = [];

        if (!is_null($addTags = Arr::get($input, 'add_tags'))) {
            $this->bulkUpdateTags($addTags, $orderIds, Order::class);
        }

        if (!is_null($removeTags = Arr::get($input, 'remove_tags'))) {
            $this->bulkRemoveTags($removeTags, $orderIds);
        }

        if (!is_null($countryId = Arr::get($input, 'country_id'))) {
            $updateColumns['shipping_contact_information'] = [
                'country_id' => $countryId
            ];
        }

        if (Arr::get($input, 'allow_partial') !== '0') {
            $updateColumns['allow_partial'] = true;
        }

        if (Arr::get($input, 'disabled_on_picking_app') !== '0') {
            $updateColumns['disabled_on_picking_app'] = true;
        }

        if (Arr::get($input, 'priority') !== '0') {
            $updateColumns['priority'] = 1;
        }

        if (!is_null($shippingMethodId = Arr::get($input, 'shipping_method_id'))) {
            $updateColumns['shipping_method_id'] = $shippingMethodId;
        }

        if (!is_null($shippingBoxId = Arr::get($input, 'shipping_box_id'))) {
            $updateColumns['shipping_box_id'] = $shippingBoxId;
        }

        foreach ($input as $key => $value) {
            if (!is_null($value) && str_contains($key, '_note')) {
                $updateColumns['append_' . $key] = $value;
            }
        }

        $updateColumns = array_merge($updateColumns, $this->bulkManageHolds($input));

        $updateBatchRequest = [];

        foreach ($orderIds as $orderId) {
            $updateBatchRequest[] = ['id' => $orderId] + $updateColumns;
        }

        $this->updateBatch(UpdateBatchRequest::make($updateBatchRequest));
    }

    /**
     * @param BulkSelectionRequest $request
     * @return void
     */
    public function bulkCancel(BulkSelectionRequest $request): void
    {
        $input = $request->validated();

        $orderIds = explode(',', Arr::get($input, 'ids'));

        foreach ($orderIds as $orderId) {
            $this->cancelOrder(Order::find($orderId));
        }
    }

    /**
     * @param BulkSelectionRequest $request
     * @return void
     */
    public function bulkFulfill(BulkSelectionRequest $request): void
    {
        $input = $request->validated();

        $orderIds = explode(',', Arr::get($input, 'ids'));

        foreach ($orderIds as $orderId) {
            $this->markAsFulfilled(Order::find($orderId));
        }
    }

    /**
     * @param BulkSelectionRequest $request
     * @return void
     */
    public function bulkArchive(BulkSelectionRequest $request): void
    {
        $input = $request->validated();

        $orderIds = explode(',', Arr::get($input, 'ids'));

        foreach ($orderIds as $orderId) {
            $this->archiveOrder(Order::find($orderId));
        }
    }

    /**
     * @param BulkSelectionRequest $request
     * @return void
     */
    public function bulkUnarchive(BulkSelectionRequest $request): void
    {
        $input = $request->validated();

        $orderIds = explode(',', Arr::get($input, 'ids'));

        foreach ($orderIds as $orderId) {
            $this->unarchiveOrder(Order::find($orderId));
        }
    }

    /**
     * @param array $input
     * @return array
     */
    private function bulkManageHolds(array $input): array
    {
        if (Arr::get($input, 'remove_all_holds') === '1') {
            return [
                'payment_hold' => 0,
                'fraud_hold' => 0,
                'address_hold' => 0,
                'operator_hold' => 0,
                'allocation_hold' => 0
            ];
        }

        $holdTypes = [
            'payment_hold',
            'fraud_hold',
            'address_hold',
            'operator_hold',
            'allocation_hold'
        ];

        $holds = [];

        foreach ($holdTypes as $hold) {
            if (Arr::get($input, 'remove_' . $hold) === '1') {
                $holds[$hold] = 0;
            } elseif (Arr::get($input, 'add_' . $hold) === '1') {
                $holds[$hold] = 1;
            }
        }

        return $holds;
    }

    /**
     * @param Order $order
     * @param $request
     * @return void
     */
    protected function updateNotes(Order $order, $request): void
    {
        if(isset($request['note_type_append']) && $request['note_text_append'] !== '') {
            $order->refresh();

            $order[$request['note_type_append']] .= empty($order[$request['note_type_append']]) ? $request['note_text_append'] : ' ' . $request['note_text_append'];
            $order->update([$request['note_type_append'] => $order[$request['note_type_append']]]);
        } else {
            if (isset($request['append_packing_note'])) {
                $order->packing_note .= empty($order->packing_note) ? $request['append_packing_note'] : ' ' . $request['append_packing_note'];
                $order->update(['packing_note' => $order->packing_note]);
                Arr::forget($request, 'append_packing_note');
            }

            if (isset($request['append_slip_note'])) {
                $order->slip_note .= empty($order->slip_note) ? $request['append_slip_note'] : ' ' . $request['append_slip_note'];
                $order->update(['slip_note' => $order->slip_note]);
                Arr::forget($request, 'append_slip_note');
            }

            if (isset($request['append_gift_note'])) {
                $order->gift_note .= empty($order->gift_note) ? $request['append_gift_note'] : ' ' . $request['append_gift_note'];
                $order->update(['gift_note' => $order->gift_note]);
                Arr::forget($request, 'append_gift_note');
            }

            if (isset($request['append_internal_note'])) {
                $order->internal_note .= empty($order->internal_note) ? $request['append_internal_note'] : ' ' . $request['append_internal_note'];
                $order->update(['internal_note' => $order->internal_note]);
                Arr::forget($request, 'append_internal_note');
            }
        }
    }

    public function updateSummedQuantities($orderIds = [])
    {
        if (!empty($orderIds)) {
            DB::transaction(function() use ($orderIds) {
                DB::update(
                    'UPDATE
                            `orders`
                        LEFT JOIN(SELECT
                                `order_id`,
                                SUM(`quantity_pending`) AS `order_items_quantity_pending_sum`,
                                SUM(`quantity_allocated`) AS `order_items_quantity_allocated_sum`,
                                SUM(`quantity_allocated_pickable`) AS `order_items_quantity_allocated_pickable_sum`
                            FROM
                                `order_items`
                            WHERE
                                `order_id` IN(' . implode(',', $orderIds) . ') AND `id` NOT IN (SELECT `order_item_kit_id` FROM `order_items` WHERE `order_id` IN (' . implode(',', $orderIds) . ') AND `order_item_kit_id` IS NOT NULL)
                            GROUP BY
                                `order_id`) `summed_order_items`
                        ON
                            `orders`.`id` = `summed_order_items`.`order_id`
                        SET
                            `quantity_pending_sum` = IFNULL(`order_items_quantity_pending_sum`, 0),
                            `quantity_allocated_sum` = IFNULL(`order_items_quantity_allocated_sum`, 0),
                            `quantity_allocated_pickable_sum` = IFNULL(`order_items_quantity_allocated_pickable_sum`, 0)
                        WHERE
                            `orders`.`id` IN(' . implode(',', $orderIds) . ');'
                );
            }, 10);
        }
    }

    public function updateSummedQuantitiesV2($orderIds = [])
    {
        foreach ($orderIds as $orderId) {
            $orderItems = OrderItem::select('order_items.*')
                ->where('order_id', $orderId)
                ->get();

            Order::where('id', $orderId)
                ->update([
                    'quantity_pending_sum' => $orderItems->sum('quantity_pending'),
                    'quantity_allocated_sum' => $orderItems->sum('quantity_allocated'),
                    'quantity_allocated_pickable_sum' => $orderItems->sum('quantity_allocated_pickable'),
                    'quantity_backordered_sum' => $orderItems->sum('quantity_backordered'),
                ]);
        }

        if (!empty($orderIds)) {
            dispatch(new RecalculateReadyToShipOrders($orderIds));
        }
    }

    /**
     * @param array $tags
     * @param array $ids
     * @return void
     */
    private function bulkRemoveTags(array $tags, array $ids): void
    {
        try {
            foreach($ids as $id) {
                $order = Order::find($id);
                $tagList = [];

                foreach ($tags as $tag) {
                    $orderTag = $order->tags()->where('name', 'LIKE', $tag)->first();

                    if ($orderTag) {
                        $order->tags()->detach($orderTag->id);
                        $tagList[] = $tag;
                    }
                }

                if (count($tagList) > 0) {
                    Order::auditCustomEvent(
                        $order,
                        'updated',
                        __('Removed <em>":tag"</em> :attribute', ['tag' => implode(', ', $tagList), 'attribute' => count($tagList) > 1 ? 'tags' : 'tag'])
                    );
                }
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    /**
     * @param Order $order
     * @param array $input
     * @return void
     */
    public function createTransferOrder(Order $order, array $input): void
    {
        $purchaseOrderInput = [
            'customer_id' => $order->customer_id,
            'warehouse_id' => $input['shipping_warehouse_id'],
            'number' => $order->number,
            'ordered_at' => Carbon::now(),
            'order_id' => $order->id
        ];

        if (isset($input['shipping_vendor_id'])) {
            $purchaseOrderInput['supplier_id'] = $input['shipping_vendor_id'];
        }

        $purchaseOrder = PurchaseOrder::create($purchaseOrderInput);

        app('purchaseOrder')->updatePurchaseOrderItems($purchaseOrder, $input['order_items']);
    }

    public function getAudits(Request $request, Order $order)
    {
        $order = $order->load(
            'audits.user.contactInformation',
            'shippingContactInformation.audits.user.contactInformation',
            'billingContactInformation.audits.user.contactInformation',
            'orderItems.audits.user.contactInformation',
            'shipments.audits.user.contactInformation',
        );

        $audits = collect([
                    $order->shippingContactInformation->audits,
                    $order->billingContactInformation->audits
                ])->reduce(function($collection, $item) {
                    if (empty($item) || $item->isEmpty()) {
                        return $collection;
                    }
                    return $collection->merge($item);
                }, $order->audits);

        $order->orderItems->map(function($orderItem, $key) use($audits) {
            $orderItem->audits->map(function($audit, $key) use($audits) {
                $audits->push($audit);
            });
        });

        $order->shipments->map(function($shipment, $key) use($audits) {
            $shipment->audits->map(function($audit, $key) use($audits) {
                $audits->push($audit);
            });
        });

        return $this->prepareEachAudits($request, $audits);
    }

    /**
     * @param OrderItem $orderItem
     * @return void
     */
    public function syncKitsWithOrderItem(OrderItem $orderItem) : void {
        if ($orderItem->product) {
            foreach ($orderItem->product->kitItems as $kitItem) {
                $quantity = $kitItem->pivot->quantity * $orderItem->quantity;

                $componentOrderItem = $orderItem->kitOrderItems()
                    ->where('product_id', $kitItem->id)
                    ->first();

                $data = [
                    'order_id' => $orderItem->order->id,
                    'product_id' => $kitItem->id,
                    'quantity' => $quantity,
                    'order_item_kit_id' => $orderItem->id
                ];

                // only add new component lines if the kit is pending
                // update the quantities even if the kit is not pending
                if ($componentOrderItem) {
                    $componentOrderItem->component_quantity = 0;
                    $componentOrderItem->update($data);
                } elseif ($orderItem->quantity_pending > 0) {
                    OrderItem::create($data);
                }
            }

            // don't touch component items if the kit is not pending
            if ($orderItem->quantity_pending > 0) {
                $componentProductIds = $orderItem->product->kitItems->pluck('pivot.child_product_id');

                $orderLinesToCancel = $orderItem->kitOrderItems()
                    ->whereNull('cancelled_at')
                    ->whereNotIn('product_id', $componentProductIds)
                    ->get();

                $orderLinesToUncanceled = $orderItem->kitOrderItems()
                    ->whereNotNull('cancelled_at')
                    ->whereIn('product_id', $componentProductIds)
                    ->get();

                foreach ($orderLinesToCancel as $orderLineToCancel) {
                    $this->cancelOrderItem($orderLineToCancel);
                }

                foreach ($orderLinesToUncanceled as $orderLineToUncancel) {
                    $this->uncancelOrderItem($orderLineToUncancel);
                }
            }
        }
    }

    /**
     * @param Product $kitParent
     * @return void
     */
    public function syncKitItemWithOrderItems(Product $kitParent): void
    {
        $orderItems = OrderItem::query()
            ->where('product_id', $kitParent->id)
            ->where('quantity_pending', '>', 0)->get();

        foreach ($orderItems as $orderItem) {
            $this->syncKitsWithOrderItem($orderItem);
        }
    }

    /**
     * @param Product $product
     * @return void
     */
    public function updateProductInOrderItems(Product $product): void
    {
        $orderItems = OrderItem::query()
            ->whereHas('order', function(Builder $query) use ($product){
                $query->where('customer_id', $product->customer_id);
            })
            ->whereNull('product_id')
            ->where('sku', $product->sku)->get();

        if ($orderItems->count() > 0) {
            foreach ($orderItems as $orderItem) {
                $orderItem->product_id = $product->id;
                $this->updateOrderItemDetails($orderItem);
                $orderItem->save();
            }
        }
    }

    /**
     * @param OrderItem $orderItem
     * @return OrderItem
     */
    public function updateOrderItemDetails(OrderItem $orderItem): OrderItem
    {
        /** @var Product $product */
        $product = Product::withTrashed()->where('id', $orderItem->product_id)->first();

        if ($product) {
            if (empty($orderItem->name)) {
                $orderItem->name = $product->name;
            }

            if (empty($orderItem->sku)) {
                $orderItem->sku = $product->sku;
            }

            if (empty($orderItem->price)) {
                $orderItem->price = $orderItem->order_item_kit_id ? 0 : $product->price;
            }

            if (empty($orderItem->weight)) {
                $orderItem->weight = $product->weight ?? 0;
            }

            if (empty($orderItem->height)) {
                $orderItem->height = $product->height ?? 0;
            }

            if (empty($orderItem->width)) {
                $orderItem->width = $product->width ?? 0;
            }

            if (empty($orderItem->length)) {
                $orderItem->length = $product->length ?? 0;
            }
        }

        return $orderItem;
    }

    /**
     * @param OrderItem $orderItem
     * @param string $status
     * @return void
     */
    public function auditChangesInParentKit(OrderItem $orderItem, string $status = ''): void
    {
        OrderItem::auditCustomEvent(
            $orderItem,
            'parent_kit',
            __(Arr::get(OrderItem::$eventMessage, 'parent_kit'), [
                'status' => $status,
                'sku' => $orderItem->sku,
                'parent_sku' => $orderItem->parentOrderItem->product->sku
            ])
        );
    }
}
