<?php

namespace App\Http\Controllers;

use App\Enums\LotPriority;
use App\Components\ProductComponent;
use App\Http\Dto\Filters\ProductsDataTableDto;
use App\Http\Requests\BulkSelectionRequest;
use App\Http\Requests\Csv\{ExportCsvRequest, ImportCsvRequest};
use App\Http\Requests\Product\{AddToLocationRequest,
    BulkEditRequest,
    ChangeLocationLotRequest,
    ChangeLocationQuantityRequest,
    RemoveFromLocationRequest,
    StoreKitItemRequest,
    StoreRequest,
    TransferRequest,
    UpdateRequest};
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Query\JoinClause;
use App\Http\Resources\{OrderItemTableResource,
    ProductLotLocationsDataTableResource,
    ProductTableResource,
    ShippedItemTableResource,
    ToteOrderItemTableResource};
use App\Models\{Customer, Location, OrderItem, PackageOrderItem, Product, Supplier, Warehouse};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\{Facades\DB, Facades\View};
use Illuminate\Validation\ValidationException;
use Webpatser\Countries\Countries;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Product::class);
        $this->middleware('3pl')->only(['transfer', 'removeFromLocation', 'addToLocation', 'changeLocationQuantity']);
    }

    public function index($keyword='')
    {
        $customers = app()->user->getSelectedCustomers()->pluck('id')->toArray();

        $data = new ProductsDataTableDto(
            Supplier::whereIn('customer_id', $customers)->get(),
            Warehouse::whereIn('customer_id', $customers)->get(),
        );

        return view('products.index', [
            'page' => 'products',
            'keyword' => $keyword,
            'data' => $data,
            'datatableOrder' => app()->editColumn->getDatatableOrder('products'),
        ]);
    }

    public function dataTable(Request $request): JsonResponse
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'products.id';
        $sortDirection = 'desc';
        $draw = $request->get('draw');
        $filterInputs =  $request->get('filter_form');

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']] ? $tableColumns[$columnOrder[0]['column']]['name'] : 'locations.id';
            $sortDirection = $columnOrder[0]['dir'];
        }

        $productCollection = app('product')->getQuery($filterInputs, $sortColumnName, $sortDirection);

        if (!empty($request->get('from_date'))) {
            $productCollection = $productCollection->where('products.updated_at', '>=', $request->get('from_date'));
        }

        $term = $request->get('search')['value'];

        if ($term) {
            $productCollection = app('product')->searchQuery($term, $productCollection);
        }

        $productIds = implode(',', $productCollection->pluck('id')->toArray());

        if ($request->get('length') && ((int) $request->get('length')) !== -1) {
            $productCollection = $productCollection->skip($request->get('start'))->limit($request->get('length'));
        }

        $products = $productCollection->get()->unique('id');
        $productCollection = ProductTableResource::collection($products);
        $visibleFields = app('editColumn')->getVisibleFields('products');

        return response()->json([
            'draw' => (int)$draw,
            'data' => $productCollection,
            'visibleFields' => $visibleFields,
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX,
            'ids' => $productIds
        ]);
    }

    /**
     * @param Product $product
     * @param Request $request
     * @return JsonResponse
     */
    public function locationsDataTable(Product $product, Request $request): JsonResponse
    {
        $draw = $request->get('draw');

        $visibleFields = app('editColumn')->getVisibleFields('product_locations');

        $locationQuery = Location::select([
            'locations.*',
            'location_product.quantity_on_hand',
            'location_product.quantity_reserved_for_picking',
            'lots.id AS lot_id',
            'lots.name AS lot_name',
            'lots.expiration_date',
            'lot_items.id AS lot_item_id',
            'lot_items.quantity_remaining',
            'contact_informations.name AS supplier_name'
        ])
            ->leftJoin('location_product', 'locations.id', 'location_product.location_id')
            ->leftJoin('lot_items', static function (JoinClause $joinClause) {
                $joinClause->on('location_product.location_id', 'lot_items.location_id')
                    ->on('location_product.product_id', 'lot_items.product_id')
                    ->where('lot_items.quantity_remaining', '>', 0)
                    ->whereNull('lot_items.deleted_at');
            })
            ->leftJoin('lots', static function (JoinClause $joinClause) {
                $joinClause->on('lot_items.lot_id', 'lots.id');
            })
            ->leftJoin('contact_informations', static function (JoinClause $joinClause) {
                $joinClause->on('object_id', 'lots.supplier_id')
                    ->where('object_type', Supplier::class);
            })
            ->where('location_product.product_id', $product->id);

        $productLocationsCollection = ProductLotLocationsDataTableResource::collection($locationQuery->get());

        return response()->json([
            'draw' => (int)$draw,
            'data' => $productLocationsCollection,
            'visibleFields' => $visibleFields,
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX
        ]);
    }

    // TODO: remove after lots are no longer a pain
    public function missingLots()
    {
        $missingLotProducts = Location::select([
            'products.sku',
            'locations.name',
            'location_product.quantity_on_hand'
        ])
            ->leftJoin('location_product', 'locations.id', 'location_product.location_id')
            ->leftJoin('products', 'products.id', 'location_product.product_id')
            ->leftJoin('lot_items', static function (JoinClause $joinClause) {
                $joinClause->on('location_product.location_id', 'lot_items.location_id')
                    ->on('location_product.product_id', 'lot_items.product_id')
                    ->where('lot_items.quantity_remaining', '>', 0)
                    ->whereNull('lot_items.deleted_at');
            })
            ->leftJoin('lots', static function (JoinClause $joinClause) {
                $joinClause->on('lot_items.lot_id', 'lots.id');
            })
            ->leftJoin('contact_informations', static function (JoinClause $joinClause) {
                $joinClause->on('object_id', 'lots.supplier_id')
                    ->where('object_type', Supplier::class);
            })
            ->where('products.lot_tracking', 1)
            ->whereNull('lots.id')
            ->where('location_product.quantity_on_hand', '>', 0)
            ->get();

        foreach ($missingLotProducts as $missingLotProduct) {
            echo $missingLotProduct->sku . ',' . $missingLotProduct->name . ',' . $missingLotProduct->quantity_on_hand . "<br />";
        }
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $createdProduct = app('product')->store($request);

        return response()->json([
            'success' => true,
            'message' => __('Product successfully created.'),
            'product' => $createdProduct->load('productImages')
        ]);
    }

    /**
     * @param Product $product
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Product $product)
    {
        $product = $product->load(['suppliers', 'kitItems']);

        return view('products.edit', [
            'product' => $product,
            'lotPriorityRules' => LotPriority::translatedValues(),
            'datatableOrder' => app()->editColumn->getDatatableOrder('product-order-items'),
            'datatableToteOrder' => app()->editColumn->getDatatableOrder('tote_order_items'),
            'datatableKitsOrder' => app()->editColumn->getDatatableOrder('product-kits'),
            'datatableShippedItemsOrder' => app()->editColumn->getDatatableOrder('package_order_items'),
            'datatableProductLocationsOrder' => app()->editColumn->getDatatableOrder('product_locations'),
            'warehouses' => $product->customer->warehouses->merge($product->customer->parentWarehouses)
        ]);
    }

    /**
     * @param UpdateRequest $request
     * @param Product $product
     * @return JsonResponse
     */
    public function update(UpdateRequest $request, Product $product): JsonResponse
    {
        $updatedProduct = app('product')->update($request, $product);

        return response()->json([
            'success' => true,
            'message' => __('Product successfully updated.'),
            'product' => $updatedProduct->load('productImages')
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function destroy(Product $product): JsonResponse
    {
        if ($product->quantity_on_hand > 0) {
            throw ValidationException::withMessages([__('Product cannot be deleted since it has inventory (' . $product->quantity_on_hand .  ' items left).')]);
        }

        app('product')->destroy(null, $product);

        return response()->json([
            'success' => true,
            'message' => __('Product successfully deleted.')
        ]);
    }

    /**
     * @param TransferRequest $request
     * @param Product $product
     * @return JsonResponse
     */
    public function transfer(TransferRequest $request, Product $product): JsonResponse
    {
        app('product')->transferInventory($request, $product);

        return response()->json([
            'success' => true,
            'message' => __('Transfer was successful.')
        ]);
    }

    /**
     * @param Product $product
     * @return JsonResponse
     */
    public function getLocations(Product $product): JsonResponse
    {
        $locations = $product->locations
            ->load(['lotItems.lot' => function($query) use ($product){
                return $query->where('product_id', $product->id)->with('supplier.contactInformation');
            }]);

        return response()->json([
            'success' => true,
            'locations' => $locations
        ]);
    }

    /**
     * @param ChangeLocationQuantityRequest $request
     * @param Product $product
     * @return void
     * @throws AuthorizationException
     */
    public function changeLocationQuantity(ChangeLocationQuantityRequest $request, Product $product): void
    {
        $this->authorize('changeLocationQuantity', $product);

        app('product')->changeLocationQuantity($request, $product);
    }

    public function removeFromLocation(RemoveFromLocationRequest $request, Product $product)
    {
        app('product')->removeFromLocation($request, $product);
    }

    public function addToLocation(AddToLocationRequest $request, Product $product)
    {
        app('product')->addToLocation($request, $product);

        return redirect()->back()->withStatus(__('Product added to location successfully.'));
    }

    public function changeLocationLot(ChangeLocationLotRequest $request, Product $product)
    {
        $this->authorize('changeLocationLot', $product);

        app('product')->changeLocationLot($request);

        return redirect()->back()->withStatus(__('Lot changed successfully.'));
    }

    public function filterLocations(Request $request, Product $product)
    {
        return app('product')->filterLocations($request, $product);
    }

    public function filterCustomers(Request $request, Product $product)
    {
        return app('product')->filterCustomers($request);
    }

    public function filterSuppliers(Request $request, Customer $customer = null)
    {
        return app('product')->filterSuppliers($request, $customer);
    }

    public function filterBySupplier(Request $request, Supplier $supplier = null)
    {
        $results = [];
        $products = app('product')->filterBySupplier($request, $supplier);

        if ($products->count()) {
            foreach ($products as $product) {
                $results[] = [
                    'id' => $product->id,
                    'text' => $product->name . ', ' . $product->sku
                ];
            }
        }

        return response()->json([
            'results' => $results
        ]);
    }

    public function filterKitProducts(Request $request, Product $product, Customer $customer = null)
    {
        return app('product')->filterKitProducts($request, $product, $customer);
    }

    public function getItem(Product $product, Request $request): \Illuminate\Contracts\View\View
    {
        $countries = Countries::query()->get();
        $kitQuantity = Product::query()->find($request->parentId)->kitItems()->where('child_product_id', $product->id)->first()->pivot->quantity;

        return View::make('shared.modals.components.product.action',compact(['product', 'countries', 'kitQuantity']));
    }

    public function editItem(Product $product, Request $request): \Illuminate\Contracts\View\View
    {
        $countries = Countries::query()->get();
        $kitQuantity = Product::query()->find($request->parentId)->kitItems()->where('child_product_id', $product->id)->first()->pivot->quantity;
        $warehouse = Product::query()->where('id', $product->id)->first()->customer->contactInformation['name'];

        return View::make('shared.modals.components.product.kitEdit', compact(['product', 'countries', 'kitQuantity', 'warehouse']));
    }

    public function updateItem(Product $product, StoreKitItemRequest $request)
    {
        $updatedKitItems = app('product')->updateKitItems($product, $request);

        return response()->json([
            'success' => true,
            'message' => __('Product successfully updated.'),
            'product' => $updatedKitItems
        ]);
    }

    public function deleteItem(Product $product, Request $request): \Illuminate\Contracts\View\View
    {
        $parentId = $request->parentId;
        $countries = Countries::query()->get();
        $kitQuantity = Product::query()->find($request->parentId)->kitItems()->where('child_product_id', $product->id)->first()->pivot->quantity;
        $warehouse = Product::query()->where('id', $product->id)->first()->customer->contactInformation['name'];

        return View::make('shared.modals.components.product.remove_component', compact(['product', 'countries', 'kitQuantity', 'warehouse', 'parentId']));
    }

    public function removeComponent(Product $kit, Product $component)
    {
        $kitItem = $kit->components()
            ->where('child_product_id', $component->id)
            ->first();

        $kitItem->delete();

        $message = __(':quantity x :sku removed from KIT', ['quantity' => $kitItem->first()->quantity, 'sku' => $component->sku]);

        Product::auditCustomEvent($kit, 'kit removed', $message);

        return redirect()->route('product.edit', $kit);
    }

    /**
     * @param Product $product
     * @return \Illuminate\Contracts\View\View
     */
    public function getLog(Product $product): \Illuminate\Contracts\View\View
    {
        $product->load('revisionHistory');

        return View::make('shared.tables.productLog', ['product' => $product]);
    }

    /**
     * @param ImportCsvRequest $request
     * @return JsonResponse
     */
    public function importCsv(ImportCsvRequest $request): JsonResponse
    {
        $message = app('product')->importCsv($request);

        return response()->json([
            'success' => true,
            'message' => __($message)
        ]);
    }

    public function importKitItemsCsv(ImportCsvRequest $request, Product $product): JsonResponse
    {
        $message = app('product')->importKitItemsCsv($request, $product);

        return response()->json([
            'success' => true,
            'message' => __($message)
        ]);
    }

    /**
     * @param ExportCsvRequest $request
     * @return mixed
     */
    public function exportCsv(ExportCsvRequest $request)
    {
        return app('product')->exportCsv($request);
    }

    public function exportKitItemsCsv(Request $request, Product $product)
    {
        return app('product')->exportKitItemsCsv($request, $product);
    }

    /**
     * @param Product $product
     * @return JsonResponse
     */
    public function recover(Product $product): JsonResponse
    {
        app('product')->recover($product);

        return response()->json([
            'success' => true,
            'message' => __('Product recovered successfully.')
        ]);
    }

    /**
     * @param Request $request
     * @param Product|null $product
     * @return JsonResponse
     */
    public function orderItemsDataTable(Request $request, Product $product = null): JsonResponse
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'ordered_at';
        $sortDirection = 'desc';

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $orderItemsCollection = OrderItem::query()
            ->with('order')
            ->where('product_id', $product->id);

        $orderItemsCollection = $orderItemsCollection->orderBy($sortColumnName, $sortDirection);

        $orders = $orderItemsCollection->skip($request->get('start'))->limit($request->get('length'))->get()->unique('id');
        $visibleFields = app('editColumn')->getVisibleFields('product-order-items');

        $orderCollection = OrderItemTableResource::collection($orders);

        return response()->json([
            'data' => $orderCollection,
            'visibleFields' => $visibleFields,
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX
        ]);
    }

    /**
     * @param Request $request
     * @param Product|null $product
     * @return JsonResponse
     */
    public function shippedItemsDataTable(Request $request, Product $product = null): JsonResponse
    {
        $tableColumns = $request->get('columns');

        $columnOrder = $request->get('order');
        $sortColumnNames = 'ordered_at, orders.created_at';
        $sortDirection = 'desc';

        if (!empty($columnOrder)) {
            $sortColumnNames = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $sortColumnNames = explode(',', $sortColumnNames);

        $customer = app()->user->getSelectedCustomers();

        $packageOrderItemsCollection = PackageOrderItem::query()
            ->whereHas('orderItem', function($query) use ($product){
                $query->where('product_id', $product->id);
            })
            ->with('lot', 'tote', 'location')
            ->with('orderItem.product.lotItems.lot.supplier.contactInformation')
            ->with('orderItem.order')
            ->join('order_items', 'package_order_items.order_item_id', '=', 'order_items.id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('locations', 'package_order_items.location_id', '=', 'locations.id')
            ->leftJoin('totes', 'package_order_items.tote_id', '=', 'totes.id')
            ->leftJoin('lots', 'package_order_items.lot_id', '=', 'lots.id')
            ->leftJoin('contact_informations', function($join) {
                $join->on('lots.id', '=', 'contact_informations.object_id');
                $join->where('contact_informations.object_type', Supplier::class);
            })
            ->select('package_order_items.*', 'orders.*');

        foreach ($sortColumnNames as $sortColumnName) {
            $packageOrderItemsCollection = $packageOrderItemsCollection->orderBy(trim($sortColumnName), $sortDirection);
        }

        if (!empty($request->get('from_date'))) {
            $packageOrderItemsCollection = $packageOrderItemsCollection->where('created_at', '>=', $request->get('from_date'));
        }

        $customers = $customer->pluck('id')->toArray();

        $packageOrderItemsCollection = $packageOrderItemsCollection->whereIn('orders.customer_id', $customers);

        $term = $request->get('search')['value'];

        if ($term) {
            $term = $term . '%';

            $packageOrderItemsCollection->where(function ($q) use ($term) {

                $q->where('orders.number', 'like', $term);
                $q->orWhere('package_order_items.serial_number', 'like', $term);
                $q->orWhere('locations.name', 'like', $term);
                $q->orWhere('lots.name', 'like', $term);
                $q->orWhere('totes.name', 'like', $term);
                $q->orWhere('contact_informations.name', 'like', $term);
            });
        }

        $packageOrderItems = $packageOrderItemsCollection->skip($request->get('start'))->limit($request->get('length'))->get();
        $visibleFields = app()->editColumn->getVisibleFields('product-order-items');

        $orderCollection = ShippedItemTableResource::collection($packageOrderItems);

        return response()->json([
            'data' => $orderCollection,
            'visibleFields' => $visibleFields,
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX
        ]);
    }

    public function toteItemsDataTable(Request $request, Product $product = null): JsonResponse
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'tote_order_items.created_at';
        $sortDirection = 'asc';

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $toteItemsCollection = app('tote')->getToteItemsQuery(null, $sortColumnName, $sortDirection);

        $toteItemsCollection = $toteItemsCollection->where('order_items.product_id', $product->id);

        if (!empty($request->get('from_date'))) {
            $toteItemsCollection = $toteItemsCollection->where('tote_order_items.created_at', '>=', $request->get('from_date'));
        }

        $customers = app()->user->getSelectedCustomers()->pluck('id')->toArray();

        $toteItemsCollection = $toteItemsCollection->whereIn('orders.customer_id', $customers);

        $term = $request->get('search')['value'];

        if ($term) {
            $term = $term . '%';

            $toteItemsCollection->where(function ($q) use ($term) {
                $q->where('orders.number', 'like', $term)
                    ->orWhere('totes.barcode', 'like', $term)
                    ->orWhere('totes.name', 'like', $term);
            });
        }

        if ($request->get('length') && ((int) $request->get('length')) !== -1) {
            $toteItemsCollection = $toteItemsCollection->skip($request->get('start'))->limit($request->get('length'));
        }

        $toteItems = $toteItemsCollection->get();
        $toteItemsCollection = ToteOrderItemTableResource::collection($toteItems);

        return response()->json([
            'data' => $toteItemsCollection,
            'visibleFields' => app()->editColumn->getVisibleFields('tote_order_items'),
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX,
        ]);
    }

    public function kitsDataTable(Request $request, Product $product = null): JsonResponse
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'products.created_at';
        $sortDirection = 'desc';

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $productCollection = Product::query()
            ->select('*', 'products.*')
            ->join('kit_items', 'kit_items.child_product_id', '=', 'products.id')
            ->whereIn('products.id', DB::table('kit_items')->where('parent_product_id', $product->id)->pluck('child_product_id')->toArray())
            ->where('kit_items.parent_product_id', $product->id)
            ->groupBy('products.id');


        $customer = app()->user->getSessionCustomer();

        $productCollection = $productCollection->orderBy($sortColumnName, $sortDirection);

        if (!empty($request->get('from_date'))) {
            $productCollection = $productCollection->where('products.created_at', '>=', $request->get('from_date'));
        }

        $term = $request->get('search')['value'];

        if ($term) {
            $productCollection->where(function ($q) use ($term) {
                $term = $term . '%';
                $q->where('products.name', 'like', $term);
            });
        }

        $visibleFields = app()->editColumn->getVisibleFields('product-order-items');
        $products = $productCollection->skip($request->get('start'))->limit($request->get('length'))->get()->unique('id');
        $productsCollection = ProductTableResource::collection($products);

        return response()->json([
            'data' => $productsCollection,
            'visibleFields' => $visibleFields,
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX
        ]);
    }

    public function barcode(Request $request, Product $product)
    {
        return app('product')->barcode($product);
    }

    public function barcodes(Request $request, Product $product)
    {
        app(ProductComponent::class)->barcodes($request, $product);

        return response()->json([
            'success' => true,
            'message' => 'Barcodes are successfully printed!',
        ]);
    }

    public function getCustomerPrinters(Product $product)
    {
        $printers = app('product')->getCustomerPrinters($product);

        return response()->json([
            'success' => true,
            'data' => $printers,
        ]);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getBulkSelectionStatus(Request $request)
    {
        return app('product')->getBulkSelectionStatus($request);
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
//        $this->authorize('update', Product::class);

        return app('product')->bulkEdit($request);
    }

    /**
     * @param BulkSelectionRequest $request
     * @return mixed
     * @throws AuthorizationException
     */
    public function bulkDelete(BulkSelectionRequest $request)
    {
//        $this->authorize('update', Product::class);

        return app('product')->bulkDelete($request);
    }

    /**
     * @param BulkSelectionRequest $request
     * @return mixed
     * @throws AuthorizationException
     */
    public function bulkRecover(BulkSelectionRequest $request)
    {
//        $this->authorize('update', Product::class);

        return app('product')->bulkRecover($request);
    }

    public function filter(Request $request, Customer $customer = null)
    {
        $results = [];
        $products = app('product')->filterProducts($request, $customer);

        foreach ($products as $product) {
            $childProducts = app('product')->queryChildProducts($product->id);

            $results[] = [
                'id' => $product->id,
                'text' => 'SKU: ' . $product->sku . ', NAME:' . $product->name,
                'sku' => $product->sku,
                'name' => $product->name,
                'image' => $product->productImages[0] ?? null,
                'price' => $product->price ?? 0,
                'cost' => $product->cost ?? 0,
                'quantity' => $product->quantity_available ?? 0,
                'type' => $product->type,
                'child_products' => $childProducts,
                'default_image' => asset('img/no-image.png'),
            ];
        }

        return response()->json([
            'results' => $results
        ]);
    }

    /**
     * @param Request $request
     * @param Product $product
     * @return mixed
     */
    public function filterLots(Request $request, Product $product)
    {
        return app('product')->filterLots($request, $product);
    }
}
