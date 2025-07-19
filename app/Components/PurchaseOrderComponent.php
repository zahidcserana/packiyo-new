<?php

namespace App\Components;

use App\Events\PurchaseOrderClosedEvent;
use App\Events\PurchaseOrderCreatedEvent;
use App\Http\Requests\Csv\{ExportCsvRequest, ImportCsvRequest};
use App\Http\Requests\PurchaseOrder\{BulkEditRequest,
    DestroyBatchRequest,
    DestroyRequest,
    FilterRequest,
    ReceiveBatchRequest,
    ReceivePurchaseOrderRequest,
    ReceiveRequest,
    StoreBatchRequest,
    StoreRequest,
    UpdateBatchRequest,
    UpdateRequest};
use App\Http\Requests\PurchaseOrderItem\RejectPurchaseOrderItemRequest;
use App\Http\Resources\{ExportResources\PurchaseOrderExportResource, PurchaseOrderCollection, PurchaseOrderResource};
use App\Jobs\Order\RecalculateReadyToShipOrders;
use App\Models\{Customer,
    Location,
    Lot,
    LotItem,
    Product,
    PurchaseOrder,
    PurchaseOrderItem,
    PurchaseOrderStatus,
    RejectedPurchaseOrderItem,
    Supplier,
    Warehouse,
    Webhook};
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\{JsonResponse, Request, Resources\Json\ResourceCollection};
use Illuminate\Support\{Arr, Collection, Facades\Session, Str};
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseOrderComponent extends BaseComponent
{
    public function store(FormRequest $request, $fireWebhook = true)
    {
        $input = $request->validated();
        $input['number'] = empty($input['number']) ? PurchaseOrder::getUniqueIdentifier(PurchaseOrder::PO_PREFIX, $input['customer_id']) : $input['number'];

        if (!Arr::has($input, 'customer_id')) {
            $input['customer_id'] = Arr::get($input, 'customer.id');
        }

        $purchaseOrderArr = Arr::except($input, ['purchase_order_items']);

        $purchaseOrder = PurchaseOrder::create($purchaseOrderArr);
        PurchaseOrderItem::disableAuditing();

        $items = Arr::get( $input, 'purchase_order_items');

        foreach ($items as $item) {
            $item['purchase_order_id'] = $purchaseOrder->id;

            PurchaseOrderItem::create($item);
        }

        $tags = Arr::get($input, 'tags');
        if (!empty($tags)) {
            $this->updateTags($tags, $purchaseOrder);
        }

        if ($fireWebhook) {
            $this->webhook(new PurchaseOrderResource($purchaseOrder), PurchaseOrder::class, Webhook::OPERATION_TYPE_STORE, $purchaseOrder->customer_id);
        }

        event(new PurchaseOrderCreatedEvent($purchaseOrder));

        return $purchaseOrder;
    }

    public function storeBatch(StoreBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $storeRequest = StoreRequest::make($record);
            $responseCollection->add($this->store($storeRequest, false));
        }

        $this->batchWebhook($responseCollection, PurchaseOrder::class, PurchaseOrderCollection::class, Webhook::OPERATION_TYPE_STORE);

        return $responseCollection;
    }

    public function update(FormRequest $request, PurchaseOrder $purchaseOrder, $fireWebhook = true)
    {
        $input = $request->validated();

        if (isset($input['purchase_order_items'])) {
            $this->updatePurchaseOrderItems($purchaseOrder, Arr::get($input, 'purchase_order_items'));
        }

        if (Arr::has($input, 'tags')) {
            $this->updateTags(Arr::get($input, 'tags'), $purchaseOrder, true);
        }

        Arr::forget($input, 'purchase_order_items');
        Arr::forget($input, 'customer_id');

        $purchaseOrder->update($input);

        if ($fireWebhook) {
            $this->webhook(new PurchaseOrderResource($purchaseOrder), PurchaseOrder::class, Webhook::OPERATION_TYPE_UPDATE, $purchaseOrder->customer_id);
        }

        return $purchaseOrder;
    }

    public function updateBatch(UpdateBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $updateRequest = UpdateRequest::make($record);
            $purchaseOrder = PurchaseOrder::where('number', $record['number'])->first();

            $responseCollection->add($this->update($updateRequest, $purchaseOrder, false));
        }

        $this->batchWebhook($responseCollection, PurchaseOrder::class, PurchaseOrderCollection::class, Webhook::OPERATION_TYPE_UPDATE);

        return $responseCollection;
    }

    public function updatePurchaseOrderItems(PurchaseOrder $purchaseOrder, $items): void
    {
        foreach ($items as $item) {
            if (!isset($item['purchase_order_item_id']) && $item['quantity'] > 0 ) {
                $item['purchase_order_id'] = $purchaseOrder->id;

                $purchaseOrderItem = $purchaseOrder->purchaseOrderItems()->where('product_id', $item['product_id'])->firstOrNew();

                $purchaseOrderItem->quantity = $item['quantity'];
                $purchaseOrderItem->product_id = $item['product_id'];
                $purchaseOrderItem->quantity_sell_ahead = $item['quantity_sell_ahead'] ?? 0;
                $purchaseOrderItem->save();

            } elseif (isset($item['purchase_order_item_id']) && $item['quantity'] == 0 ) {
                PurchaseOrderItem::where('id', $item['purchase_order_item_id'])->delete();
            } elseif (isset($item['purchase_order_item_id']) ) {
                $purchaseOrderItem = PurchaseOrderItem::where('id', $item['purchase_order_item_id'])->first();
                $purchaseOrderItem->product_id = $item['product_id'];
                $purchaseOrderItem->quantity = $item['quantity'];
                $purchaseOrderItem->quantity_sell_ahead = $item['quantity_sell_ahead'];
                $purchaseOrderItem->save();
            }
        }
    }

    public function destroy(DestroyRequest $request, PurchaseOrder $purchaseOrder, $fireWebhook = true)
    {
        $purchaseOrder->purchaseOrderItems()->delete();

        $purchaseOrder->delete();

        $response = ['id' => $purchaseOrder->id, 'customer_id' => $purchaseOrder->customer_id];

        if ($fireWebhook) {
            $this->webhook($response, PurchaseOrder::class, Webhook::OPERATION_TYPE_DESTROY, $purchaseOrder->customer_id);
        }

        return $response;
    }

    public function destroyBatch(DestroyBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $destroyRequest = DestroyRequest::make($record);
            $purchaseOrder = PurchaseOrder::find($record['id']);

            $responseCollection->add($this->destroy($destroyRequest, $purchaseOrder, false));
        }

        $this->batchWebhook($responseCollection, PurchaseOrder::class, ResourceCollection::class, Webhook::OPERATION_TYPE_DESTROY);

        return $responseCollection;
    }

    public function receive(ReceiveRequest $request, PurchaseOrder $purchaseOrder, PurchaseOrderItem $purchaseOrderItem)
    {
        $input = $request->validated();

        $location = Location::find(Arr::get($input, 'location_id'));
        $lot = Lot::find(Arr::get($input, 'lot_id'));
        $quantityReceived = Arr::get($input, 'quantity_received');

        app('inventoryLog')->adjustInventory(
            $location,
            $purchaseOrderItem->product,
            $quantityReceived,
            InventoryLogComponent::OPERATION_TYPE_RECEIVE,
            $purchaseOrder,
            $lot
        );

        $purchaseOrderItem->quantity_received += $quantityReceived;
        $purchaseOrderItem->quantity_sell_ahead -= $quantityReceived;
        $purchaseOrderItem->quantity_sell_ahead = max($purchaseOrderItem->quantity_sell_ahead, 0);

        $purchaseOrderItem->location_id = $location->id;
        $purchaseOrderItem->save();

        PurchaseOrder::auditCustomEvent(
            $purchaseOrder,
            'po-item-received',
            __(':quantity of SKU :sku received.', [
                    'quantity' => $quantityReceived,
                    'sku' => $purchaseOrderItem->product->sku
                ]
            )
        );

        dispatch(new RecalculateReadyToShipOrders());

        return $purchaseOrderItem;
    }

    public function receiveBatch(ReceiveBatchRequest $request, PurchaseOrder $purchaseOrder, $fireWebhook = true)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $receiveRequest = ReceiveRequest::make($record);
            $purchaseOrderItem = PurchaseOrderItem::find($record['purchase_order_item_id']);
            $responseCollection->add($this->receive($receiveRequest, $purchaseOrder, $purchaseOrderItem));
        }

        $purchaseOrder->refresh();

        if ($fireWebhook == true) {
            $this->webhook(new PurchaseOrderResource($purchaseOrder), PurchaseOrder::class, Webhook::OPERATION_TYPE_RECEIVE, $purchaseOrder->customer_id);
        }

        return $responseCollection;
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
    public function filterWarehouses(Request $request, ?Customer $customer): JsonResponse
    {
        $term = $request->get('term');
        $results = [];

        if ($customer) {
            $warehouseQuery = Warehouse::whereCustomerId($customer->id);

            if ($customer->parent) {
                $warehouseQuery = $warehouseQuery->orWhere('customer_id', $customer->parent_id);
            }
        } else {
            $customers = app('user')->getSelectedCustomers()->pluck('id')->toArray();

            $warehouseQuery = Warehouse::whereIn('customer_id', $customers);
        }

        if ($term) {
            $warehouseQuery = $warehouseQuery->whereHas('contactInformation', function($query) use ($term) {
                $term .= '%';

                $query->where('name', 'like', $term)
                    ->orWhere('company_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('zip', 'like', $term)
                    ->orWhere('city', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        $warehouses = $warehouseQuery->get();

        if ($warehouses->count()) {
            foreach ($warehouses as $warehouse) {
                $results[] = [
                    'id' => $warehouse->id,
                    'text' => $warehouse->contactInformation->name
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
    public function filterSuppliers(Request $request, Customer $customer = null): JsonResponse
    {
        $term = $request->get('term');
        $results = [];

        if ($term) {
            if (is_null($customer)) {
                $customers = app()->user->getSelectedCustomers()->pluck('id')->toArray();

                $suppliers = Supplier::whereIn('customer_id', $customers);
            } else {
                $suppliers = $customer->suppliers();
            }

            $suppliers = $suppliers->whereHas('contactInformation', function ($query) use ($term) {
                $term = $term . '%';

                $query->where('name', 'like', $term)
                    ->orWhere('company_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('zip', 'like', $term)
                    ->orWhere('city', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            })->get();
        }

        foreach ($suppliers as $supplier) {
            if ($supplier->count()) {
                $results[] = [
                    'id' => $supplier->id,
                    'text' =>$supplier->contactInformation->name . ', ' . $supplier->contactInformation->email . ', ' . $supplier->contactInformation->zip . ', ' . $supplier->contactInformation->city . ', ' . $supplier->contactInformation->phone
                ];
            }
        }

        return response()->json([
            'results' => $results
        ]);
    }

    public function filterProducts(Request $request): JsonResponse
    {
        $results = [];

        if ($request->get('supplier')) {
            $supplier = Supplier::find($request->get('supplier'));
            $products = $supplier->products();
        } else {
            $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();
            $products = Product::whereIn('customer_id', $customerIds);
        }

        if (isset($request->action)) {
            if ($request->action === 'backorders') {
                $products = $products->where('quantity_backordered', '>', 0);
            } else if ($request->action === 'reorders') {
                $products = $products->whereRaw('quantity_on_hand < reorder_threshold');
            }
        } elseif ($term = $request->get('term')) {
            $term = $term . '%';

            $products = $products->where(function ($query) use ($term) {
                $query->where('products.name', 'like', $term)
                    ->orWhere('products.sku', 'like', $term);
            });
        }

        $products = $products->get();

        if (!is_null($products)) {
            foreach ($products as $product) {
                if (isset($request->action)) {
                    if ($request->action === 'backorders') {
                        $quantity = $product->quantity_backordered;
                    } else if ($request->action === 'products') {
                        $quantity = 1;
                    } else {
                        $quantity = $product->quantity_reorder;
                    }
                } else {
                    $quantity = 1;
                }

                $results[] = [
                    'id' => $product->id,
                    'text' => 'SKU: ' . $product->sku . ', NAME:' . $product->name,
                    'barcode' => $product->barcode,
                    'quantity' => $quantity
                ];
            }
        }

        return response()->json([
            'results' => $results
        ]);
    }

    /**
     * @param Request $request
     * @param Warehouse $warehouse
     * @return JsonResponse
     */
    public function filterLocations(Request $request, Warehouse $warehouse): JsonResponse
    {
        $term = $request->get('term');
        $results = [];

        if ($term) {
            $term .= '%';

            $term = $term . '%';

            $locations = $warehouse->locations()
                ->with(['products', 'placedLotItems.lot.product'])
                ->where('name', 'like', $term)
                ->orderByDesc('is_receiving')
                ->orderBy('name')
                ->get();

            foreach ($locations as $location) {
                $lotInformation = $location->placedLotItems
                    ->sortByDesc('updated_at')
                    ->pluck('productSkuAndLotName')
                    ->join(', ');

                $locationProductInformation = $location->products->pluck('pivot.product.sku')->join(', ');

                $results[] = [
                    'id' => $location->id,
                    'text' => $location->name
                        . ': '
                        . ($lotInformation ?: $locationProductInformation)
                ];
            }
        }

        return response()->json([
            'results' => $results
        ]);
    }

    public function getOrderStatus(Request $request, Customer $customer): JsonResponse
    {
        $results = [];

        $orderStatuses = PurchaseOrderStatus::where('customer_id', $customer->id)->get();

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

    public function filter(FilterRequest $request, $customerIds)
    {
        $query = PurchaseOrder::query();

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

        $query->when(count($customerIds) > 0, function ($q) use($customerIds){
            return $q->whereIn('customer_id', $customerIds);
        });

        return $query->paginate();
    }

    /**
     * @param ReceivePurchaseOrderRequest $request
     * @param PurchaseOrder $purchaseOrder
     * @param $fireWebhook
     * @return bool
     * @throws Exception
     */
    public function updatePurchaseOrder(ReceivePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder, $fireWebhook = true): bool
    {
        $input = $request->input();
        $records = [];

        foreach ($input['quantity_received'] as $key => $item) {
            $locationId = Arr::get($input, 'location_id.' . $key);
            $quantityReceived = Arr::get($input, 'quantity_received.' . $key);
            $productId = Arr::get($input, 'product_id.' . $key);

            if (!empty($locationId) && !empty($quantityReceived)) {
                $records[$key] = [
                    'purchase_order_item_id' => $key,
                    'quantity_received' => $quantityReceived,
                    'location_id' => $locationId,
                    'lot_id' => Arr::get($input, 'lot_id.' . $key),
                    'product_id' => $productId,
                    'lot_tracking' => Arr::get($input, 'lot_tracking.' . $key),
                ];
            }
        }

        foreach ($records as $record) {
            $receiveRequest = ReceiveRequest::make($record);
            $purchaseOrderItem = PurchaseOrderItem::find($record['purchase_order_item_id']);
            $this->receive($receiveRequest, $purchaseOrder, $purchaseOrderItem);

            $purchaseOrderItem->refresh();
        }

        if ($fireWebhook) {
            $this->webhook(new PurchaseOrderResource($purchaseOrder), PurchaseOrder::class, Webhook::OPERATION_TYPE_RECEIVE, $purchaseOrder->customer_id);
        }

        return true;
    }

    public function rejectPurchaseOrderItem(RejectPurchaseOrderItemRequest $request, PurchaseOrderItem $purchaseOrderItem): void
    {
        $input = $request->input();
        $quantityRejected = $purchaseOrderItem->quantity_rejected;

        foreach ($input['quantity'] as $key => $rejectedQuantity) {
            $rejectedItem = new RejectedPurchaseOrderItem();

            $rejectedItem->purchase_order_item_id = $purchaseOrderItem->id;
            $rejectedItem->quantity = $rejectedQuantity;
            $rejectedItem->reason = $input['reason'][$key];
            $rejectedItem->note = $input['note'][$key] ?? null;

            $rejectedItem->save();
            $quantityRejected += $rejectedItem->quantity;
        }

        $purchaseOrderItem->quantity_rejected = $quantityRejected;
        $purchaseOrderItem->quantity_pending -= $quantityRejected;

        if ($purchaseOrderItem->quantity_pending < $purchaseOrderItem->quantity_sell_ahead) {
            $purchaseOrderItem->quantity_sell_ahead = $purchaseOrderItem->quantity_pending;
        }

        $purchaseOrderItem->save();
    }

    public function search($term)
    {
        $customerIds = Auth()->user()->customerIds();

        $purchaseOrdersCollection = PurchaseOrder::query();

        if (!empty($customerIds)) {
            $purchaseOrdersCollection = $purchaseOrdersCollection->whereIn('purchase_orders.customer_id', $customerIds);
        }

        $filters = json_decode($term);

        if ($filters->filterArray ?? false) {
            foreach ($filters->filterArray as $key => $filter) {
                if ($filter->columnName === 'dates_between') {
                    $dates = explode(" ", $filter->value);
                    $from = Arr::get($dates, '0', '');
                    $to = Arr::get($dates, '2', '');

                    $today = Carbon::today()->toDateString();
                    $tomorrow = Carbon::tomorrow()->toDateString();

                    $purchaseOrdersCollection = $purchaseOrdersCollection->whereBetween('purchase_orders.ordered_at', [
                        empty($from)
                            ? Carbon::now()->subDays(14)->toDateString() : date($from),
                        empty($to)
                            ? $tomorrow : Carbon::parse($to)->addDay()->toDate()->format('Y-m-d')
                    ]);

                    unset($filters->filterArray[$key]);
                }

                if ($filter->columnName === 'table_search') {
                    $term = $filter->value ?: null;
                    unset($filters->filterArray[$key]);
                }
            }

            $purchaseOrdersCollection = $purchaseOrdersCollection->where(function ($query) use ($filters) {
                foreach ($filters->filterArray as $filter) {
                    if ($filter->columnName !== 'ordering' && !empty($filter->value)) {
                        $query->where($filter->columnName, $filter->value);
                    }
                }
            });
        }

        if ($term) {
            $term = $term . '%';

            $purchaseOrdersCollection
                ->where(function ($query) use ($term) {
                    $query->whereHas('customer.contactInformation', function ($query) use ($term) {
                        $query->where('name', 'like', $term);
                    })
                        ->orWhereHas('warehouse.contactInformation', function ($query) use ($term) {
                            $query->where('name', 'like', $term);
                        })
                        ->orWhereHas('supplier.contactInformation', function ($query) use ($term) {
                            $query->where('name', 'like', $term);
                        })

                        ->orWhereHas('warehouse.contactInformation', function ($query) use ($term) {
                            $query->where('name', 'like', $term);
                        })
                        ->orWhereRaw('REPLACE(purchase_orders.status, "_", " ") = "' . str_replace('%', '', $term) . '"')
                        ->orWhere('number', 'like', $term);
                });
        }

        return $purchaseOrdersCollection;
    }

    public function closePurchaseOrder(PurchaseOrder $purchaseOrder): void
    {
        if (is_null($purchaseOrder->closed_at)) {
            $purchaseOrder->closed_at = Carbon::now();
            $purchaseOrder->save();

            foreach ($purchaseOrder->purchaseOrderItems as $purchaseOrderItem) {
                if ($purchaseOrderItem->quantity_sell_ahead > 0) {
                    $purchaseOrderItem->quantity_sell_ahead = 0;

                    $purchaseOrderItem->save();
                }
            }

            event(new PurchaseOrderClosedEvent($purchaseOrder));
        }
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
            app('csv')->unsetCsvHeader($importLines, 'purchase_order_number'),
            PurchaseOrderExportResource::columns()
        );

        if (!empty($importLines)) {
            $storedCollection = new Collection();
            $updatedCollection = new Collection();

            $purchaseOrdersToImport = [];

            $parentCustomerIds = [];

            foreach ($importLines as $importLine) {
                $data = [];
                $customerId = $input['customer_id'];
                $data['customer_id'] = $customerId;

                if (!isset($parentCustomerIds[$customerId])) {
                    $customer = Customer::find($customerId);

                    $parentCustomerIds[$customerId] = $customer->parent_id;
                }

                $data['parent_customer_id'] = $parentCustomerIds[$customerId];

                foreach ($columns as $columnsIndex => $column) {
                    if (Arr::has($importLine, $columnsIndex)) {
                        $data[$column] = Arr::get($importLine, $columnsIndex);
                    }
                }

                if (!Arr::has($purchaseOrdersToImport, $data['purchase_order_number'])) {
                    $purchaseOrdersToImport[$data['purchase_order_number']] = [];
                }

                $purchaseOrdersToImport[$data['purchase_order_number']][] = $data;
            }

            $purchaseOrderToImportIndex = 0;

            foreach ($purchaseOrdersToImport as $number => $purchaseOrderToImport) {
                $purchaseOrder = PurchaseOrder::where('customer_id', $purchaseOrderToImport[0]['customer_id'])->where('number', $number)->first();

                if ($purchaseOrder) {
                    $updatedCollection->add($this->update($this->createRequestFromImport($purchaseOrderToImport, $purchaseOrder), $purchaseOrder,false));
                } else {
                    $storedCollection->add($this->store($this->createRequestFromImport($purchaseOrderToImport)));
                }

                Session::flash('status', ['type' => 'info', 'message' => __('Importing :current/:total purchase orders', ['current' => ++$purchaseOrderToImportIndex, 'total' => count($purchaseOrdersToImport)])]);
                Session::save();
            }

            $this->batchWebhook($storedCollection, PurchaseOrder::class, PurchaseOrderCollection::class, Webhook::OPERATION_TYPE_STORE);
            $this->batchWebhook($updatedCollection, PurchaseOrder::class, PurchaseOrderCollection::class, Webhook::OPERATION_TYPE_UPDATE);
        }

        Session::flash('status', ['type' => 'success', 'message' => __('Purchase orders were successfully imported!')]);

        return __('Purchase orders were successfully imported!');
    }

    /**
     * @param ExportCsvRequest $request
     * @return StreamedResponse
     */
    public function exportCsv(ExportCsvRequest $request): StreamedResponse
    {
        $input = $request->validated();
        $search = $input['search']['value'];

        $purchaseOrders = $this->getQuery($request->get('filter_form'));

        if ($search) {
            $purchaseOrders = $this->searchQuery($search, $purchaseOrders);
        }

        $csvFileName = Str::kebab(auth()->user()->contactInformation->name) . '-purchase-orders-export.csv';

        return app('csv')->export($request, $purchaseOrders->get(), PurchaseOrderExportResource::columns(), $csvFileName, PurchaseOrderExportResource::class);
    }

    /**
     * @param array $data
     * @param PurchaseOrder|null $purchaseOrder
     * @return StoreRequest|UpdateRequest
     */
    private function createRequestFromImport(array $data, PurchaseOrder $purchaseOrder = null)
    {
        $purchaseOrderStatus = PurchaseOrderStatus::where('customer_id', $data[0]['customer_id'])->where('name', $data[0]['status'])->first();

        $warehouse = Warehouse::whereIn('customer_id', [$data[0]['customer_id'], $data[0]['parent_customer_id']])->whereHas('contactInformation', function($query) use ($data) {
            $query->where('name', $data[0]['warehouse']);
        })->first();

        if ($supplierName = Arr::get($data, '0.supplier')) {
            $supplier = Supplier::where('customer_id', $data[0]['customer_id'])
                ->whereHas('contactInformation', fn (Builder $query) => $query->where('name', $supplierName))
                ->first();
        }

        $requestData = [
            'number' => $data[0]['purchase_order_number'],
            'customer_id' => $data[0]['customer_id'],
            'warehouse_id' => $warehouse->id ?? null,
            'supplier_id' => $supplier->id ?? null,
            'purchase_order_status_id' => $purchaseOrderStatus->id ?? null,
            'ordered_at' => Carbon::parse($data[0]['ordered_at'])->toDateTimeString(),
            'expected_at' => Carbon::parse($data[0]['expected_at'])->toDateTimeString(),
            'tracking_number' => Arr::get($data, '0.tracking_number'),
            'tracking_url' => Arr::get($data, '0.tracking_url'),
        ];

        foreach ($data as $key => $line) {
            $product = Product::where('customer_id', $data[0]['customer_id'])->where('sku', trim($line['sku']))->first();

            $requestData['purchase_order_items'][$key] = [
                'product_id' => $product->id ?? null,
                'quantity' => (int) $line['quantity'],
                'quantity_sell_ahead' => (int) $line['quantity_sell_ahead'],
            ];

            if ($purchaseOrder) {
                $purchaseOrderItem = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)->where('product_id', $requestData['purchase_order_items'][$key]['product_id'])->first();

                if (!is_null($purchaseOrderItem)) {
                    $requestData['purchase_order_items'][$key]['purchase_order_item_id'] = $purchaseOrderItem->id;
                }
            }
        }

        return $purchaseOrder ? UpdateRequest::make($requestData, $purchaseOrder->id) : StoreRequest::make($requestData);
    }

    /**
     * @param $filterInputs
     * @param mixed $sortColumnName
     * @param mixed $sortDirection
     * @return Builder
     */
    public function getQuery($filterInputs, string $sortColumnName = 'purchase_orders.id', string $sortDirection = 'desc'): Builder
    {
        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        $filterCustomerId = Arr::get($filterInputs, 'customer_id');

        if ($filterCustomerId && $filterCustomerId != 'all') {
            $customerIds = array_intersect($customerIds, [$filterCustomerId]);
        }

        return PurchaseOrder::with([
            'customer.contactInformation',
            'supplier.contactInformation',
            'warehouse.contactInformation',
            'purchaseOrderStatus',
            'tags'
        ])
            ->leftJoin('contact_informations AS warehouse_contact_information', static function (JoinClause $joinClause) {
                $joinClause->where('warehouse_contact_information.object_type', Warehouse::class)
                    ->on('warehouse_contact_information.object_id', 'purchase_orders.warehouse_id');
            })
            ->leftJoin('contact_informations AS supplier_contact_information', static function (JoinClause $joinClause) {
                $joinClause->where('supplier_contact_information.object_type', Supplier::class)
                    ->on('supplier_contact_information.object_id', 'purchase_orders.supplier_id');
            })
            ->whereIn('purchase_orders.customer_id', $customerIds)
            ->where(function ($query) use ($filterInputs) {
            // Find by filter result
            $query
                // Order Status
                ->when($filterInputs['purchase_order_status'] ?? false, function ($q) use ($filterInputs) {
                    if ($filterInputs['purchase_order_status'] === 'closed') {
                        return $q->whereNotNull('closed_at');
                    }

                    if ($filterInputs['purchase_order_status'] === 'pending') {
                        return $q->whereNull(['closed_at', 'purchase_order_status_id']);
                    }

                    if ($filterInputs['purchase_order_status'] === 'all') {
                        return $q;
                    }

                    return $q
                        ->where('purchase_order_status_id', $filterInputs['purchase_order_status'])
                        ->whereNull('closed_at');
                })
                // Received
                ->when($filterInputs['received'] ?? false, function ($q) use ($filterInputs) {
                    if ((int)$filterInputs['received'] === 0) {
                        return $q->whereNull('received_at');
                    }

                    return $q->whereNotNull('received_at');
                })

                // Warehouse
                ->when($filterInputs['warehouse'] ?? false, function ($q) use ($filterInputs) {
                    return $q->where('warehouse_id', $filterInputs['warehouse']);
                })

                // Tags
                ->when($filterInputs['tags'] ?? false, function ($q) use ($filterInputs) {
                    $filterTags = (array) $filterInputs['tags'];
                    return $q->whereHas('tags', function($q) use ($filterTags) {
                        $q->whereIn('name', $filterTags);
                    });
                });
        })
            ->select('purchase_orders.*')
            ->groupBy('purchase_orders.id')
            ->orderBy($sortColumnName, $sortDirection);
    }

    /**
     * @param string $term
     * @param $purchaseOrdersCollection
     */
    public function searchQuery(string $term, $purchaseOrdersCollection)
    {
        $term = $term . '%';

        $purchaseOrdersCollection->where(function ($query) use ($term) {
            $query->where(function ($query) use ($term) {
                $query->where('number', 'LIKE', $term)
                    ->orWhere('tracking_number', 'LIKE', $term);
            });

            $query->orWhereHas('warehouse.contactInformation', function ($query) use ($term) {
                $query->where('name', 'like', $term);
            })
                ->orWhereHas('customer.contactInformation', function ($query) use ($term) {
                    $query->where('name', 'like', $term);
                })
                ->orWhereHas('supplier.contactInformation', function ($query) use ($term) {
                    $query->where('name', 'like', $term);
                })
                ->orWhereHas('purchaseOrderStatus', function ($query) use ($term) {
                    $query->where('name', 'like', $term);
                })
                ->orWhere('number', 'like', $term);
        });

        return $purchaseOrdersCollection;
    }

    /**
     * @param BulkEditRequest $request
     * @return void
     */
    public function bulkEdit(BulkEditRequest $request): void
    {
        $input = $request->validated();

        if (Arr::exists($input, 'tags')) {
            $this->bulkUpdateTags(Arr::get($input, 'tags'), Arr::get($input, 'ids'), PurchaseOrder::class);
        }
    }
}
