<?php

namespace App\Components;

use App\Enums\Source;
use App\Features\AllowNonSellableAllocation;
use App\Http\Requests\BulkSelectionRequest;
use App\Jobs\AllocateInventoryJob;
use App\Jobs\Order\SyncKitProductWithOrderItemsJob;
use App\Http\Requests\Csv\{ExportCsvRequest, ImportCsvRequest};
use App\Http\Requests\ProductBarcode\StoreRequest as ProductBarcodeStoreRequest;
use App\Http\Requests\Product\{AddToLocationRequest,
    BulkEditRequest,
    ChangeLocationLotRequest,
    ChangeLocationQuantityRequest,
    DestroyBatchRequest,
    DestroyRequest,
    FilterRequest,
    StoreBatchRequest,
    StoreRequest,
    TransferRequest,
    UpdateBatchRequest,
    UpdateRequest};
use App\Http\Resources\{ExportResources\KitsExportResource,
    ExportResources\ProductExportResource,
    ProductCollection,
    ProductResource};
use App\Models\{Customer,
    Image,
    Location,
    LocationProduct,
    Lot,
    LotItem,
    OrderItem,
    PrintJob,
    Product,
    ProductWarehouse,
    ProductBarcode,
    Supplier,
    Webhook};
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\{JsonResponse, Request, Resources\Json\ResourceCollection};
use Illuminate\Support\{Arr, Collection, Facades\DB, Facades\Log, Facades\Session, Facades\Storage, Str};
use Illuminate\Validation\ValidationException;
use PDF;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webpatser\Countries\Countries;
use Laravel\Pennant\Feature;

class ProductComponent extends BaseComponent
{
    public function __construct()
    {

    }

    public function store(FormRequest $request, $fireWebhook = true, ?Source $source = null)
    {
        $input = $request->validated();

        if (!Arr::get($input, 'country_of_origin')) {
            $country = Countries::where('iso_3166_2', Arr::get($input, 'country_of_origin_code'))->first();

            if ($country) {
                Arr::set($input, 'country_of_origin', $country->id);
            }
        }

        foreach (Product::$columnDecimal as $key) {
            if (empty($input[$key])) Arr::set($input, $key, 0);
        }
        foreach (Product::$columnDouble as $key) {
            if (empty($input[$key])) Arr::set($input, $key, null);
        }

        if (!Arr::has($input, 'customer_id')) {
            $input['customer_id'] = Arr::get($input, 'customer.id');
        }

        // backwards compatibility for older integrations. Should remove when we're sure it's not used anymore
        if (Arr::has($input, 'kit_type') && !Arr::has($input, 'type')) {
            if (!is_null($input['kit_type'])) {
                $input['type'] = match (intval($input['kit_type'])) {
                    0 => Product::PRODUCT_TYPE_REGULAR,
                    1 => Product::PRODUCT_TYPE_STATIC_KIT,
                    2 => Product::PRODUCT_TYPE_DYNAMIC_KIT
                };
            }
        }

        $product = Product::create($input);
        Product::disableAuditing();
        Image::disableAuditing();

        $customer = Customer::find($input['customer_id']);

        if (!empty($input['suppliers']) && count($input['suppliers'])) {
            $product->suppliers()->sync($input['suppliers']);
        }

        if (isset($input['product_images'])) {
            $this->updateProductImageURLs($product, $input['product_images']);
        }

        if ($images = $request->file('file')) {
            $this->updateProductImages($product, $images, $customer);
        }

        if (!empty($input['type']) && ($input['type'] === Product::PRODUCT_TYPE_DYNAMIC_KIT || $input['type'] === Product::PRODUCT_TYPE_STATIC_KIT) && !empty($input['kit_items']) && count($input['kit_items'])) {
            $syncItems = [];

            foreach ($input['kit_items'] as $item) {
                if (!Arr::has($item, 'id') && $componentSku = Arr::has($item, 'sku')) {
                    $component = $customer->products()
                        ->where('sku', $componentSku)
                        ->first();

                    if ($component) {
                        $item['id'] = $component->id;
                    }
                }

                $syncItems[$item['id']] = ['quantity' => $item['quantity']];
            }

            $product->kitItems()->sync($syncItems, false);

            $product->type = $input['type'];
        } elseif (!empty($input['type']) && $input['type'] === Product::PRODUCT_TYPE_VIRTUAL) {
            $product->type = Product::PRODUCT_TYPE_VIRTUAL;
        } else {
            $product->type = Product::PRODUCT_TYPE_REGULAR;
        }

        if (!empty($input['product_barcodes'])) {
            $this->saveProductBarcodes($product, $input);
        }

        $product->save();

        $tags = Arr::get($input, 'tags');
        if (!empty($tags)) {
            $this->updateTags($tags, $product);
        }

        if ($fireWebhook) {
            $this->webhook(new ProductResource($product), Product::class, Webhook::OPERATION_TYPE_STORE, $product->customer_id);
        }

        return $product;
    }

    public function storeBatch(StoreBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $storeRequest = StoreRequest::make($record);
            $responseCollection->add($this->store($storeRequest, false));
        }

        $this->batchWebhook($responseCollection, Product::class, ProductCollection::class, Webhook::OPERATION_TYPE_STORE);

        return $responseCollection;
    }

    public function update(FormRequest $request, Product $product = null, $fireWebhook = true, ?Source $source = null)
    {
        $input = $request->validated();

        if (!Arr::get($input, 'country_of_origin')) {
            $country = Countries::where('iso_3166_2', Arr::get($input, 'country_of_origin_code'))->first();

            if ($country) {
                Arr::set($input, 'country_of_origin', $country->id);
            }
        }

        if (is_null($product)) {
            $product = Product::where('customer_id', Arr::get($input, 'customer_id'))->where('sku', Arr::get($input, 'sku'))->first();
        }

        if (Arr::has($input, 'kit_type') && !Arr::has($input, 'type')) {
            if (!is_null($input['kit_type'])) {
                $input['type'] = match (intval($input['kit_type'])) {
                    0 => Product::PRODUCT_TYPE_REGULAR,
                    1 => Product::PRODUCT_TYPE_STATIC_KIT,
                    2 => Product::PRODUCT_TYPE_DYNAMIC_KIT
                };
            }
        }

        if (array_key_exists($type = Arr::get($input, 'type'), Product::PRODUCT_TYPES)) {
            if ($type === Product::PRODUCT_TYPE_REGULAR || $type === Product::PRODUCT_TYPE_VIRTUAL) {
                $product->kitItems()->detach();
            }
        } else {
            $input['type'] = $product->type;
        }


        if (isset($input['priority_counting_requested_at']) && $input['priority_counting_requested_at'] == '1') {
            $input['priority_counting_requested_at'] = now();
        } else {
            $input['priority_counting_requested_at'] = null;
        }

        //Quantity reserved is an array when multiwarehouse is enabled
        if (isset($input['quantity_reserved']) && is_array($input['quantity_reserved'])) {
            $quantityReserved = $input['quantity_reserved'];
            unset($input['quantity_reserved']);

            foreach ($quantityReserved as $warehouseId => $quantity) {
                ProductWarehouse::updateOrCreate([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId
                ], [
                    'quantity_reserved' => $quantity
                ]);
            }
        }

        foreach ($input as $key => $value) {
            if (in_array($key, Product::$columnDecimal)) {
                Arr::set($input, $key, number_format(floatval($value), 2, '.', ''));
            } elseif (in_array($key, Product::$columnDouble) && empty($value)) {
                Arr::set($input, $key, null);
            }
        }

        $product->update($input);

        if (!empty($input['update_vendor'])) {
            if (empty($input['suppliers']) || !count($input['suppliers'])) {
                $input['suppliers'] = [];
            }
            $product->suppliers()->sync($input['suppliers']);
        }

        if (isset($input['product_images'])) {
            $this->updateProductImageURLs($product, $input['product_images']);
        }

        if ($images = $request->file('file')) {
            $this->updateProductImages($product, $images, $product->customer);
        }

        if (!empty($input['update_kit']) || !empty($input['kit_items'])) {
            if ($product->isKit()) {
                $syncItems = [];

                foreach ($input['kit_items'] as $item) {
                    if (isset($item['id']) && ! is_null($item['quantity'])) {
                        $syncItems[$item['id']] = ['quantity' => $item['quantity']];
                    }
                }

                $oldKitItems = $product->kitItems()->get();

                $product->kitItems()->sync($syncItems);

                $product->save();

                $newKitItems = $product->kitItems()->get();

                Product::auditKitItems($product, $oldKitItems, $newKitItems);
            }

            if ($product->isKit() && Arr::get($input, 'update_orders')) {
                SyncKitProductWithOrderItemsJob::dispatch($product);
            }
        } else if ($product->wasChanged(['type'])) {
            $this->syncKitProductWithOrderItems($product);
        }

        if (!empty($input['product_barcodes'])) {
            $this->saveProductBarcodes($product, $input);
        }

        if (!empty($input['product_locations'])) {
            $detachableLocations = $product->locations->pluck('id');

            if (count($input['product_locations'])) {
                foreach ($input['product_locations'] as $key => $location) {
                    if (!is_null($location)) {
                        $lot = null;

                        if (!empty($input['product_lots'][$key])) {
                            $lot = Lot::find($input['product_lots'][$key]['id']);
                        }

                        $addToLocationRequest = AddToLocationRequest::make([
                            'product_id' => $product->id,
                            'location_id' => $location['id'],
                            'lot_id' => $lot->id ?? null,
                            'quantity' => $location['quantity']
                        ]);
                        $this->addToLocation($addToLocationRequest, $product);

                        $detachableLocations = $detachableLocations->filter(function($item) use($location) {
                            return $item != $location['id'];
                        });
                    }
                }
            }

            $detachableLocations = $detachableLocations->toArray();

            if (!empty($detachableLocations) && count($detachableLocations)) {
                foreach ($detachableLocations as $location) {
                    $this->removeFromLocation($product, $location);
                }
            }
        }

        if (Arr::exists($input, 'tags')) {
            $this->updateTags(Arr::get($input, 'tags'), $product, true);
        }

        if ($fireWebhook) {
            $this->webhook(new ProductResource($product), Product::class, Webhook::OPERATION_TYPE_UPDATE, $product->customer_id);
        }

        return $product;
    }

    public function updateBatch(UpdateBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $updateRequest = UpdateRequest::make($record);

            if (isset($record['id'])) {
                $responseCollection->add($this->update($updateRequest, Product::find($record['id']), false));
            } else {
                $responseCollection->add($this->update($updateRequest, null, false));
            }
        }

        $this->batchWebhook($responseCollection, Product::class, ProductCollection::class, Webhook::OPERATION_TYPE_UPDATE);

        return $responseCollection;
    }

    public function destroy(DestroyRequest $request = null, Product $product = null, $fireWebhook = true)
    {
        if (!$product) {
            $input = $request->validated();
            $product = Product::where('id', $input['id'])->first();
        }

        $response = null;

        if (!empty($product) && $product->quantity_on_hand == 0) {
            $product->delete();

            $response = collect(['id' => $product->id, 'sku' => $product->sku, 'customer_id' => $product->customer_id]);

            if ($fireWebhook) {
                $this->webhook($response, Product::class, Webhook::OPERATION_TYPE_DESTROY, $product->customer_id);
            }
        }

        return $response;
    }

    public function destroyBatch(DestroyBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $destroyRequest = DestroyRequest::make($record);

            $response = $this->destroy($destroyRequest, null, false);

            if (!empty($response)) {
                $responseCollection->add($response);
            }
        }

        $this->batchWebhook($responseCollection, Product::class, ResourceCollection::class, Webhook::OPERATION_TYPE_DESTROY);

        return $responseCollection;
    }

    // TODO: figure out where used
    // Used on app!
    public function changeLocationQuantity(ChangeLocationQuantityRequest $request, Product $product): void
    {
        $input = $request->validated();

        $location = Location::find(Arr::get($input, 'location_id'));

        $quantity = Arr::get($input, 'quantity');
        if ($quantity === null) {
            $quantity = Arr::get($input, 'quantity_available');

            if ($quantity !== null) {
                if ($product->locations->count() == 1) {
                    $quantity += $product->orderItem()->sum('quantity_pending');
                }
            }
        }

        app('inventoryLog')->adjustInventory(
            $location,
            $product,
            $quantity,
            InventoryLogComponent::OPERATION_TYPE_MANUAL
        );
    }

    public function removeFromLocation(Product $product, $locationId): void
    {
        LotItem::where('location_id', $locationId)
            ->whereHas('lot', function($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->delete();

        $location = $product->locations()->where('location_id', $locationId)->first();

        if ($location && $location->pivot->quantity_on_hand == 0) {
            app('inventoryLog')->adjustInventory(
                $location,
                $product,
                0,
                InventoryLogComponent::OPERATION_TYPE_MANUAL
            );

            $product->locations()->detach($location);
            $product->save();
        }
    }

    public function removeEmptyLocations(Product $product): void
    {
        $locations = $product->locations()->wherePivot('quantity_on_hand', 0)
            ->get();

        foreach ($locations as $location) {
            $this->removeFromLocation($product, $location->id);
        }

        $product->save();
    }

    public function addToLocation(AddToLocationRequest $request, Product $product): void
    {
        app('inventoryLog')->adjustInventory(
            Location::find($request->get('location_id')),
            $product,
            $request->get('quantity'),
            InventoryLogComponent::OPERATION_TYPE_MANUAL,
            null,
            $request::$lot
        );
    }

    public function transferInventory(TransferRequest $request, Product $product)
    {
        $fromLot = Lot::find($request->get('lot_id'));
        $fromLocation = Location::find($request->get('from_location_id'));
        $toLocation = Location::find($request->get('to_location_id'));

        app('inventoryLog')->adjustInventory(
            $toLocation,
            $product,
            $request->get('quantity'),
            InventoryLogComponent::OPERATION_TYPE_TRANSFER,
            $fromLocation,
            $fromLot
        );
    }

    public function changeLocationLot(ChangeLocationLotRequest $request)
    {
        $input = $request->validated();
        $lotId = Arr::get($input, 'lot_id');

        if ($lotItemId = Arr::get($input, 'lot_item_id')) {
            LotItem::find($lotItemId)->update([
                'lot_id' => $lotId
            ]);
        } else {
            $locationProduct = LocationProduct::where('product_id', Arr::get($input, 'product_id'))
                ->where('location_id', Arr::get($input, 'location_id'))
                ->firstOrFail();

            LotItem::updateOrCreate([
                'product_id' => $locationProduct->product_id,
                'location_id' => $locationProduct->location_id,
            ], [
                'lot_id' => $lotId,
                'quantity_added' => $locationProduct->quantity_on_hand,
                'quantity_removed' => 0
            ]);
        }
    }

    public function filterCustomers(Request $request): JsonResponse
    {
        $term = $request->get('term');
        $results = [];

        if ($term) {
            $contactInformation = Customer::whereHas('contactInformation', function($query) use ($term) {
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

    public function filterLocations(Request $request, ?Product $product): JsonResponse
    {
        if ($term = $request->get('term')) {
            if ($product) {
                $customers = [$product->customer->id];

                if ($product->customer->parent_id) {
                    $customers[] = $product->customer->parent_id;
                }
            } else {
                $customers = app('user')->getSelectedCustomers()->pluck('id')->toArray();
            }

            $term = $term . '%';

            $results = Location::where('name', 'like', $term)
                ->where('id', '!=', $request->get('from_location_id'))
                ->whereHas('warehouse', function(Builder $q) use ($customers) {
                    $q->whereIntegerInRaw('customer_id', $customers);
                })
                ->get()
                ->map(function($location) {
                    $lotInformation = $location->placedLotItems
                        ->sortByDesc('updated_at')
                        ->pluck('productSkuAndLotName')
                        ->join(', ');

                    return [
                        'id' => $location->id,
                        'text' => $location->name . ' (' . $location->warehouse->contactInformation->name . ') '
                            . ($lotInformation ? ': ' . $lotInformation : '')
                    ];
                })->toArray();
        }

        return response()->json([
            'results' => $results ?? [],
        ]);
    }

    /**
     * @param Request $request
     * @param Product|null $product
     * @param Customer|null $customer
     * @return JsonResponse
     */
    public function filterKitProducts(Request $request, Product|null $product = null, Customer|null $customer = null): JsonResponse
    {
        $term = $request->get('term');
        $excludedIds = $request->get('excludedIds');
        $results = [];

        if ($term) {
            $term = $term . '%';

            $query = Product::query()
                ->where('type', Product::PRODUCT_TYPE_REGULAR)
                ->whereDoesntHave('kitItems')
                ->where(function (Builder $q) use ($term) {
                    $q->where('sku', 'like', $term)
                        ->orWhere('name', 'like', $term);
            });

            if (!is_null($product)) {
                $query->where('id', '!=', $product->id);
                $customer = $product->customer;
            }

            if (!is_null($customer)) {
                $query->where('customer_id', $customer->id);
            }

            if (!empty($excludedIds) && count($excludedIds)) {
                $query->whereNotIn('id', $excludedIds);
            }

            $products = $query->get();

            foreach ($products as $product) {
                $results[] = [
                    'id' => $product->id,
                    'text' => $product->name
                ];
            }
        }

        return response()->json([
            'results' => $results
        ]);
    }

    public function getCustomerProduct(Customer $customer): LengthAwarePaginator
    {
        return $customer->products()->paginate();
    }

    /**
     * @param FilterRequest $request
     * @param $customerIds
     * @return LengthAwarePaginator
     */
    public function filter(FilterRequest $request, $customerIds)
    {
        $query = Product::query();

        $query->when($request['customer_id'], function ($q) use($request){
            return $q->where('customer_id', $request['customer_id']);
        });

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

        $query->when($request['location_id'], function ($q) use($request){
            return $q->whereHas('location', function ($loc) use($request){
                return $loc->where('locations.id', $request['location_id']);
            });
        });

        $query->when(count($customerIds) > 0, function ($q) use($customerIds){
            return $q->whereIn('customer_id', $customerIds);
        });

        return $query->paginate();
    }

    /**
     * @param Request $request
     * @param Supplier|null $supplier
     * @return Builder[]|Collection|\Illuminate\Database\Eloquent\Collection|Product[]
     */
    public function filterBySupplier(Request $request, Supplier $supplier = null)
    {
        $term = '%'.$request->get('term') . '%';

        if (is_null($supplier)) {
            return collect([]);
        }

        $prodQuery = Product::whereHas('suppliers', function ($query) use ($term, $supplier) {
            $query->where('suppliers.id', $supplier->id);
        })
        ->where(function ($query) use ($term) {
            $query->where('name', 'like', $term);
            $query->orWhere('sku', 'like', $term);
        });

        if ($request->get('lot')) {
            $prodQuery->where('lot_tracking', '1');
        }

        return $prodQuery->get();
    }
    /**
     * @param Request $request
     * @param Customer|null $customer
     * @return JsonResponse
     */
    public function filterSuppliers(Request $request, Customer $customer = null): JsonResponse
    {
        $term = $request->get('term');
        $excludedIds = $request->get('excludedIds') ?? [];

        $results = [];

        if ($term) {
            if (is_null($customer)) {
                // need to revisit everything about this. The reason Adnan added this
                // is that this method is also called from lots page. We should refactor it
                // so the lots page has its own method or add a similar method specifically for
                // existing suppliers on a product.
                if ($request->exists('product_id') && !$request->is('product/*')) {
                    /** @var Product $product */
                    $product = Product::find($request->get('product_id'));

                    $suppliers = $product->customer->suppliers();
                } else {
                    $customers = app('user')->getSelectedCustomers()->pluck('id')->toArray();

                    $suppliers = Supplier::whereIn('customer_id', $customers);
                }
            } else {
                if ($request->exists('product_id')) {
                    /** @var Product $product */
                    $product = Product::find($request->get('product_id'));

                    $suppliers = $product->suppliers();
                } else {
                    $suppliers = $customer->suppliers();
                }
            }

            $results = $suppliers->whereNotIn('suppliers.id', $excludedIds)
                ->whereHas('contactInformation', function ($query) use ($term) {
                    $term = $term . '%';

                    $query->where('name', 'like', $term)
                        ->orWhere('company_name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('zip', 'like', $term)
                        ->orWhere('city', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                })
                ->get()
                ->map(function(Supplier $supplier){
                    return [
                        'id' => $supplier->id,
                        'text' => implode(', ', [
                            $supplier->contactInformation->name,
                            $supplier->contactInformation->email,
                            $supplier->contactInformation->zip,
                            $supplier->contactInformation->city,
                            $supplier->contactInformation->phone,
                        ])
                    ];
                });
        }

        return response()->json([
            'results' => $results
        ]);
    }

    public function updateProductImageURLs(Product $product, $items)
    {
        foreach ($items as $item) {
            $image = Image::where('source', $item['source'])->where('object_type', Product::class)->where('object_id', $product->id)->first();

            if ($image) {
                continue;
            }

            $imageObj = new Image();
            $imageObj->source = $item['source'];
            $imageObj->filename = '';
            $imageObj->object()->associate($product);
            $imageObj->save();
        }
    }

    public function updateProductImages(Product $product, $images, Customer $customer)
    {
        foreach ($images as $image) {
            $filename = $image->store('public');
            $source = url(Storage::url($filename));

            $imageObj = new Image();
            $imageObj->source = $source;
            $imageObj->filename = $filename;
            $imageObj->object()->associate($product);
            $imageObj->save();
        }
    }

    public function getCountries()
    {
        return Countries::all();
    }

    public function recover(Product $product): void
    {
        $product->deleted_at = null;
        $product->save();
    }

    public function importCsv(ImportCsvRequest $request): string
    {
        $input = $request->validated();

        $importLines = app('csv')->getCsvData($input['import_csv']);

        $columns = array_intersect(
            app('csv')->unsetCsvHeader($importLines, 'sku'),
            ProductExportResource::columns()
        );

        if (!empty($importLines)) {
            $storedCollection = new Collection();
            $updatedCollection = new Collection();

            foreach ($importLines as $importLineIndex => $importLine) {
                $data = [];
                $data['customer_id'] = $input['customer_id'];

                foreach ($columns as $columnIndex => $column) {
                    if (Arr::has($importLine, $columnIndex)) {
                        $data[$column] = Arr::get($importLine, $columnIndex);
                    }
                }

                $product = Product::where('customer_id', $data['customer_id'])->where('sku', $data['sku'])->first();

                if ($product) {
                    $updatedCollection->add($this->update($this->createRequestFromImport($data, $product, true), $product, false));
                } else {
                    $storedCollection->add($this->store($this->createRequestFromImport($data), false));
                }

                Session::flash('status', ['type' => 'info', 'message' => __('Importing :current/:total products', ['current' => $importLineIndex + 1, 'total' => count($importLines)])]);
                Session::save();
            }

            $this->batchWebhook($storedCollection, Product::class, ProductCollection::class, Webhook::OPERATION_TYPE_STORE);
            $this->batchWebhook($updatedCollection, Product::class, ProductCollection::class, Webhook::OPERATION_TYPE_UPDATE);
        }

        Session::flash('status', ['type' => 'success', 'message' => __('Products were successfully imported!')]);

        return __('Products were successfully imported!');
    }

    public function importKitsCsv(ImportCsvRequest $request): string
    {
        $input = $request->validated();
        $kitsToImport = [];
        $messages = [];
        $storedCollection = new Collection();
        $updatedCollection = new Collection();

        $importLines = app('csv')->getCsvData($input['import_csv']);

        $columns = array_intersect(
            app('csv')->unsetCsvHeader($importLines, 'parent_sku'),
            [
                'parent_sku',
                'child_sku',
                'quantity',
                'update_orders'
            ]
        );

        if (!empty($importLines)) {
            foreach ($importLines as $importLine) {
                $data = [];
                $data['customer_id'] = $input['customer_id'];

                foreach ($columns as $columnIndex => $column) {
                    if (Arr::has($importLine, $columnIndex)) {
                        $data[$column] = Arr::get($importLine, $columnIndex);
                    }
                }

                $kitsToImport[$data['parent_sku']][] = $data;
            }
        }

        $importLineIndex = 0;

        if (!empty($kitsToImport)) {
            foreach ($kitsToImport as $kitSku => $components) {
                $product = Product::where('sku', $kitSku)
                            ->where('customer_id', $input['customer_id'])
                            ->first();

                if (!$product || !$this->existsAsKit($product)) {
                    throw ValidationException::withMessages([
                        'parent_sku' => __('Kit :sku doesn\'t exist', ['sku' => $kitSku])
                    ]);
                }

                if ($this->existsAsComponent($product)) {
                    throw ValidationException::withMessages([
                        'parent_sku' => __(':sku cannot be a kit - it\'s already a component.', ['sku' => $kitSku])
                    ]);
                }

                $componentsToImport = [];
                $componentsToImport['kit_sku'] = $kitSku;
                $componentsToImport['customer_id'] = $input['customer_id'];
                $componentsToImport['components'] = $components;

                DB::transaction(function() use ($componentsToImport, $storedCollection, $updatedCollection, $product) {
                    $updatedCollection->add($this->update(
                        $this->createRequestFromImport($componentsToImport, $product, true),
                        $product,
                        false,
                        Source::MANUAL_VIA_FILE_UPLOAD
                    ));
                }, 10);

                $messages[] = [
                    'type' => 'info',
                    'message' => __('Importing :current/:total inventory lines', ['current' => ++$importLineIndex, 'total' => count($importLines)])
                ];
            }
        }

        $messages[] = [
            'type' => 'success',
            'message' => __('Orders were successfully imported!')
        ];

        Session::flash('status', $messages);
        Session::save();

        $this->batchWebhook($updatedCollection, Product::class, ProductCollection::class, Webhook::OPERATION_TYPE_UPDATE);

        return __('Kit items were successfully imported!');
    }

    private function downloadImageFromCsv(string $image, Product $product): void
    {
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            $imageContent = file_get_contents($image);
            $filename = basename($image);

            Storage::disk('public')->put($filename, $imageContent);
            $source = url(Storage::url($filename));

            $imageObj = new Image();
            $imageObj->source = $source;
            $imageObj->filename = $filename;
            $imageObj->object()->associate($product);
            $imageObj->save();
        }
    }

    /**
     * @param ExportCsvRequest $request
     * @return StreamedResponse
     */
    public function exportCsv(ExportCsvRequest $request): StreamedResponse
    {
        $input = $request->validated();
        $search = $input['search']['value'];

        $products = $this->getQuery($request->get('filter_form'));

        if ($search) {
            $products = $this->searchQuery($search, $products);
        }

        $csvFileName = Str::kebab(auth()->user()->contactInformation->name) . '-products-export.csv';

        return app('csv')->export($request, $products->get(), ProductExportResource::columns(), $csvFileName, ProductExportResource::class);
    }

    /**
     * @param array $data
     * @param Product|null $product
     * @param bool $update
     * @return StoreRequest|UpdateRequest
     */
    private function createRequestFromImport(array $data, Product $product = null, bool $update = false)
    {
        $requestData = $data;

        unset($requestData['image'], $requestData['locations'], $requestData['new_locations']);

        if (Arr::has($data, 'image')) {
            $requestData['product_images'] = [
                [
                    'source' => Arr::get($data, 'image')
                ]
            ];
        }

        if (isset($requestData['vendor']) && !empty($requestData['vendor'])) {
            unset($requestData['vendor']);

            $supplierNames = explode(';', $data['vendor']);
            $supplierIds = [];

            foreach ($supplierNames as $supplierName) {
                $supplier = Supplier::whereHas('contactInformation', static function ($query) use ($supplierName) {
                    $query->where('name', 'like', trim($supplierName));
                })
                    ->where('customer_id', $requestData['customer_id'])
                    ->first();

                if ($supplier) {
                    $supplierIds[] = $supplier->id;
                }
            }

            if (!empty($supplierIds)) {
                if ($product) {
                    $requestData['update_vendor'] = true;
                }

                $requestData['suppliers'] = $supplierIds;
            }
        }

        $countryOfOrigin = Countries::find(Arr::get($requestData, 'country_of_origin'));

        if (!$countryOfOrigin) {
            $countryOfOrigin = Countries::where('iso_3166_2', Arr::get($requestData, 'country_of_origin'))->first();
        }

        $requestData['country_of_origin'] = $countryOfOrigin->id ?? null;

        if ($product && isset($data['components']) && !empty($data['components'])) {
            $requestData['kit_items'] = [];
            if ($product && $product->isKit()) {
                foreach ($data['components'] as $line) {
                    if (!is_null($line['quantity'])) {
                        $requestData['kit_items'][] = [
                            'sku' => Arr::get($line, 'child_sku'),
                            'quantity' => Arr::get($line, 'quantity'),
                        ];

                        if (Arr::get($line, 'update_orders') === 'yes') {
                            $requestData['update_orders'] = true;
                        }
                    }
                }
                unset($requestData['components']);

            } else {
                $requestData['kit_sku'] = Arr::get($data, 'kit_sku');
            }

            $requestData['type'] = $product->type;
        }

        return $update ? UpdateRequest::make($requestData, $product->id ?? null) : StoreRequest::make($requestData);
    }

    /**
     * @param $filterInputs
     * @param string $sortColumnName
     * @param string $sortDirection
     * @return Builder
     */
    public function getQuery($filterInputs, string $sortColumnName = 'products.id', string $sortDirection = 'desc')
    {
        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        $filterCustomerId = Arr::get($filterInputs, 'customer_id');

        if ($filterCustomerId && $filterCustomerId !== 'all') {
            $customerIds = array_intersect($customerIds, [$filterCustomerId]);
        }

        $productCollection = Product::with([
                'customer.contactInformation.country',
                'customer.settings',
                'tags',
                'productImages',
                'suppliers.contactInformation.country',
                'purchaseOrderLine',
                'productBarcodes',
                'productWarehouses.warehouse.contactInformation'
            ])
            ->join('customers', 'products.customer_id', '=', 'customers.id')
            ->join('contact_informations AS customer_contact_information', 'customers.id', '=', 'customer_contact_information.object_id')
            ->when(Arr::get($filterInputs, 'supplier'), function ($q) {
                return $q->leftJoin('product_supplier', 'products.id', '=', 'product_supplier.product_id');
            })
            ->when(Arr::get($filterInputs, 'warehouse'), function ($q) {
                return $q->leftJoin('location_product', 'products.id', '=', 'location_product.product_id')
                    ->leftJoin('locations', 'location_product.location_id', '=', 'locations.id')
                    ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id');
            })
            ->where('customer_contact_information.object_type', Customer::class)
            ->whereIn('products.customer_id', $customerIds)
            ->where(function ($query) use ($filterInputs) {
                // Find by filter result

                // Allocated
                if (isset($filterInputs['allocated'])) {
                    $query->where('products.quantity_allocated', $filterInputs['allocated'] ? '>' : '=', 0);
                }

                // Backordered
                if (isset($filterInputs['backordered'])) {
                    $query->where('products.quantity_backordered', $filterInputs['backordered'] ? '>' : '=', 0);
                }

                // In Stock
                if (isset($filterInputs['in_stock'])) {
                    $query->where('products.quantity_available', $filterInputs['in_stock'] ? '>' : '=', 0);
                }

                // Is Kit
                if (isset($filterInputs['is_kit'])) {
                    if ($filterInputs['is_kit']) {
                        $query->whereHas('kitItems');
                    } else {
                        $query->whereDoesntHave('kitItems');
                    }
                }

                // Warehouse
                if (!is_null($filterInputs) && $filterInputs['warehouse'] && $filterInputs['warehouse'] !== 0) {
                    $query->where('warehouses.id', $filterInputs['warehouse']);
                }

                // Vendor
                if (!is_null($filterInputs) && $filterInputs['supplier'] && $filterInputs['supplier'] !== 0) {
                    $query->where('product_supplier.supplier_id', $filterInputs['supplier']);
                }

                // Tags
                if (!is_null($filterInputs) && !empty($filterInputs['tags'])) {
                    $filterTags = (array) $filterInputs['tags'];
                    $query->whereHas('tags', function($query) use ($filterTags) {
                        $query->whereIn('name', $filterTags);
                    });
                }

                // Inventory sync
                if (!is_null($filterInputs) && Arr::has($filterInputs, 'inventory_sync')) {
                    $inventorySync = Arr::get($filterInputs, 'inventory_sync');

                    if (!is_null($inventorySync)) {
                        $query->where('inventory_sync', $inventorySync);
                    }
                }
            })
            ->select('products.*')
            ->groupBy('products.id')
            ->orderBy($sortColumnName, $sortDirection);

        // Show deleted products
        if (isset($filterInputs['show_deleted'])) {
            if ($filterInputs['show_deleted'] == 1) {
                $productCollection->onlyTrashed();
            } elseif ($filterInputs['show_deleted'] == 2) {
                $productCollection->withTrashed();
            }
        }

        return $productCollection;
    }

    /**
     * @param string $term
     * @param $productCollection
     * @return mixed
     */
    public function searchQuery(string $term, $productCollection): mixed
    {
        $term = $term . '%';

        return $productCollection
            ->where(function ($query) use ($term) {
                $query->where('products.name', 'like', $term)
                    ->orWhere('products.sku', 'like', $term)
                    ->orWhere('products.barcode', 'like', $term);
            });
    }

    /**
     * @param Request $request
     * @return Product|Builder|\Illuminate\Database\Query\Builder
     */
    public function getKitsQuery(Request $request): Builder|Product|\Illuminate\Database\Query\Builder
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'products.id';
        $sortDirection = 'desc';
        $filterInputs = $request->get('filter_form');

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']] ? $tableColumns[$columnOrder[0]['column']]['name'] : 'locations.id';
            $sortDirection = $columnOrder[0]['dir'];
        }

        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        $filterCustomerId = Arr::get($filterInputs, 'customer_id');

        if ($filterCustomerId && $filterCustomerId != 'all') {
            $customerIds = array_intersect($customerIds, [$filterCustomerId]);
        }

        $kitsCollection = Product::join('customers', 'products.customer_id', '=', 'customers.id')
            ->join('contact_informations AS customer_contact_information', 'customers.id', '=', 'customer_contact_information.object_id')
            ->where('customer_contact_information.object_type', Customer::class)
            ->whereIn('products.customer_id', $customerIds)
            ->whereHas('kitItems')
            ->select('products.*')
            ->orderBy($sortColumnName, $sortDirection);

        if (!empty($request->get('from_date'))) {
            $kitsCollection = $kitsCollection->where('products.updated_at', '>=', $request->get('from_date'));
        }

        if ($request->has('search')) {
            $term = '%' . $request->get('search')['value'] . '%';

            $kitsCollection
                ->where(function ($query) use ($term) {
                    $query->where('products.name', 'like', $term)
                        ->orWhere('products.sku', 'like', $term)
                        ->orWhere('products.barcode', 'like', $term);
                });
        }

        if ($request->get('length') && ((int)$request->get('length')) !== -1) {
            $kitsCollection = $kitsCollection->skip($request->get('start'))->limit($request->get('length'));
        }
        return $kitsCollection;
    }

    public function exportKitsCsv(Request $request): StreamedResponse
    {
        $customers = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        $kits = DB::table('kit_items')
            ->join('products as kit_parent', 'kit_parent.id', '=', 'kit_items.parent_product_id')
            ->join('products as kit_child', 'kit_child.id', '=', 'kit_items.child_product_id')
            ->select('kit_items.*', 'kit_parent.sku as parent_sku', 'kit_child.sku as child_sku')
            ->whereIn('kit_parent.customer_id', $customers)
            ->get();

        $kitItems = new Collection();

        foreach ($kits as $kit) {
            $kitItems->push([
                'parent_sku' => $kit->parent_sku,
                'child_sku' => $kit->child_sku,
                'quantity' => $kit->quantity,
                'update_orders' => __('no')
            ]);
        }

        $csvFileName = Str::kebab(auth()->user()->contactInformation->name) . '-kits-export.csv';

        return app('csv')->export($request, $kitItems, KitsExportResource::columns(), $csvFileName, KitsExportResource::class);
    }

    public function updateKitItems(Product $product, $request)
    {
        if ($request['kit-quantity']) {
            foreach ($request['kit-quantity'] as $key => $requestKitQuantity) {
                $kitItems = DB::table('kit_items')
                    ->where('parent_product_id', $product->id)
                    ->where('child_product_id', $key)
                    ->update(['quantity' => $requestKitQuantity]);
            }
        }

        $array = $product->kitItems->pluck('id')->toArray();
        foreach ($request->kit_items as $kit_item) {
            if (in_array($kit_item['id'], $array)) {
                $kitItems = DB::table('kit_items')
                    ->where('parent_product_id', $product->id)
                    ->where('child_product_id', $kit_item['id'])
                    ->update(['quantity' => $kit_item['quantity']]);
            } else{
                $kitItems = DB::table('kit_items')
                    ->insert([
                        'parent_product_id' => $product->id,
                        'child_product_id' => $kit_item['id'],
                        'quantity' => $kit_item['quantity']
                    ]);
            }
        }

        return $kitItems ?? '';
    }

    public function barcode(Product $product)
    {
        $generator = new BarcodeGeneratorPNG();
        $barcodes[] = [
            'name' => $product->name,
            'barcode' => $generator->getBarcode($product->barcode, $generator::TYPE_CODE_128),
            'number' => $product->barcode
        ];

        foreach ($product->productBarcodes as $productBarcode) {
            $barcodes[] = [
                'name' => $product->name,
                'barcode' => $generator->getBarcode($productBarcode->barcode, $generator::TYPE_CODE_128),
                'number' => $productBarcode->barcode
            ];
        }

        $barcodes = array_unique($barcodes, SORT_REGULAR);

        $fileName = $product->name . Str::random(20) . '.pdf';

        $paperWidth = paper_width($product->customer_id, 'barcode');
        $paperHeight = paper_height($product->customer_id, 'barcode');

        return PDF::loadView('pdf.barcodes', ['barcodes' => $barcodes])
                    ->setPaper([0, 0, $paperWidth, $paperHeight])
                    ->stream($fileName);
    }

    public function barcodes(Request $request, Product $product)
    {
        $count = (int) $request->to_print;
        $printer_id = (int) $request->printer_id;

        if ($printer_id) {
            for ($i = 0; $i < $count; $i++) {
                PrintJob::create([
                    'object_type' => Product::class,
                    'object_id' => $product->id,
                    'url' => route('product.barcode', [
                        'product' => $product,
                    ]),
                    'printer_id' => $printer_id,
                    'user_id' => auth()->user()->id,
                ]);
            }
        }
    }

    public function getCustomerPrinters(Product $product): array
    {
        return $product->customer
            ->printers
            ->pluck('hostnameAndName', 'id')
            ->toArray();
    }

    /**
     * @param BulkEditRequest $request
     * @return void
     */
    public function bulkEdit(BulkEditRequest $request): void
    {
        $input = $request->validated();
        $productIds = explode(',', Arr::get($input, 'ids'));
        $updateColumns = [];

        if (!is_null($addTags = Arr::get($input, 'add_tags'))) {
            $this->bulkUpdateTags($addTags, $productIds, Product::class);
            Arr::forget($input, 'add_tags');
        }

        if (!is_null($removeTags = Arr::get($input, 'remove_tags'))) {
            $this->bulkRemoveTags($removeTags, $productIds);
            Arr::forget($input, 'remove_tags');
        }

        if (Arr::get($input, 'lot_tracking') !== '0') {
            $updateColumns['lot_tracking'] = 1;
        }

        $updateColumns['inventory_sync'] = Arr::get($input, 'inventory_sync');

        if (Arr::get($input, 'has_serial_number') !== '0') {
            $updateColumns['has_serial_number'] = 1;
        }

        if (Arr::get($input, 'priority_counting_requested_at') !== '0') {
            $updateColumns['priority_counting_requested_at'] = 1;
        }

        if (!is_null(Arr::get($input, 'remove_empty_locations'))) {
            foreach ($productIds as $productId) {
                $this->removeEmptyLocations(Product::withTrashed()->find($productId));
            }
        }

        if ($hsCode = Arr::get($input, 'hs_code')) {
            $updateColumns['hs_code'] = $hsCode;
        }

        if (!is_null($reorderThreshold = Arr::get($input, 'reorder_threshold'))) {
            $updateColumns['reorder_threshold'] = $reorderThreshold;
        }

        if (!is_null($quantityReorder = Arr::get($input, 'quantity_reorder'))) {
            $updateColumns['quantity_reorder'] = $quantityReorder;
        }

        if (!is_null($quantityReserved = Arr::get($input, 'quantity_reserved'))) {
            if (!is_null($warehouseId = Arr::get($input, 'warehouse_id'))) {
                $this->bulkUpdateQuantityReserved($productIds, $warehouseId, $quantityReserved);
                Arr::forget($input, ['quantity_reserved', 'warehouse_id']);
            } else {
                $updateColumns['quantity_reserved'] = $quantityReserved;
            }
        }

        if ($notes = Arr::get($input, 'notes')) {
            $updateColumns['notes'] = $notes;
        }

        if (!is_null($countryId = Arr::get($input, 'country_id'))) {
            $updateColumns['country_of_origin'] = $countryId;
        }

        if ($customsDescription = Arr::get($input, 'customs_description')) {
            $updateColumns['customs_description'] = $customsDescription;
        }

        if ($customsPrice = Arr::get($input, 'customs_price')) {
            $updateColumns['customs_price'] = $customsPrice;
        }

        if (!is_null($vendorId = Arr::get($input, 'vendor_id'))) {
            $updateColumns['update_vendor'] = true;
            $updateColumns['suppliers'][] = $vendorId;
        }

        $updateBatchRequest = [];

        foreach ($productIds as $productId) {
            $updateBatchRequest[] = ['id' => $productId] + $updateColumns;
        }

        $this->updateBatch(UpdateBatchRequest::make($updateBatchRequest));
    }

    /**
     * @param BulkSelectionRequest $request
     * @return void
     * @throws ValidationException
     */
    public function bulkDelete(BulkSelectionRequest $request): void
    {
        $input = $request->validated();

        $productIds = explode(',', Arr::get($input, 'ids'));

        $productSkus = Product::whereIn('id', $productIds)->where('quantity_on_hand', '>', 0)->pluck('sku')->toArray();

        if (count($productSkus) > 0) {
            throw ValidationException::withMessages([__('SKUs ' . implode(', ', $productSkus) . ' cannot be archived because they have inventory.')]);
        } else {
            Product::whereIn('id', $productIds)->delete();
        }
    }

    /**
     * @param BulkSelectionRequest $request
     * @return void
     */
    public function bulkRecover(BulkSelectionRequest $request): void
    {
        $input = $request->validated();

        $productIds = explode(',', Arr::get($input, 'ids'));

        Product::whereIn('id', $productIds)
            ->withTrashed()
            ->update([
                'deleted_at' => null
            ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getBulkSelectionStatus(Request $request): JsonResponse
    {
        $deleted = true;
        $existing = true;

        $products = Product::whereIn('id', $request->get('ids'))
            ->withTrashed()
            ->get();

        foreach ($products as $product) {
            if ($product->trashed()) {
                $existing = false;
            } else {
                $deleted = false;
            }
        }

        return response()->json([
            'results' => compact('deleted', 'existing')
        ]);
    }

    /**
     * @param $productId
     * @return Product[]|Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function queryChildProducts($productId)
    {
        return Product::query()
            ->with('productImages')
            ->select('*', 'products.*')
            ->join('kit_items', 'kit_items.child_product_id', '=', 'products.id')
            ->whereIn('products.id', DB::table('kit_items')
                ->where('parent_product_id', $productId)
                ->pluck('child_product_id')->toArray())
            ->where('kit_items.parent_product_id', $productId)
            ->groupBy('products.id')->get();
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
                $product = Product::find($id);
                $tagList = [];

                foreach ($tags as $tag) {
                    $productTag = $product->tags()->where('name', 'LIKE', $tag)->first();

                    if ($productTag) {
                        $product->tags()->detach($productTag->id);
                        $tagList[] = $tag;
                    }
                }

                if (count($tagList) > 0) {
                    Product::auditCustomEvent(
                        $product,
                        'updated',
                        __('Removed <em>":tag"</em> :attribute', ['tag' => implode(', ', $tagList), 'attribute' => count($tagList) > 1 ? 'tags' : 'tag'])
                    );
                }
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    public function saveProductBarcodes($product, $input) {
        if (!empty($input['product_barcodes']['barcode'])) {
            $barcodeData = [];

            for ($i=0; $i < count($input['product_barcodes']['barcode']); $i++) {
                if ($input['product_barcodes']['barcode'][$i]) {
                    $barcodeInput = [
                        'product_id' => $product->id,
                        'barcode' => $input['product_barcodes']['barcode'][$i],
                        'quantity' => $input['product_barcodes']['quantity'][$i] ?? 0,
                        'description' => $input['product_barcodes']['description'][$i] ?? ''
                    ];

                    $barcode = ProductBarcodeStoreRequest::make($barcodeInput);
                    $barcodeData[] = new ProductBarcode($barcode->validated());
                }
            }

            $product->productBarcodes()->forceDelete();
            $product->productBarcodes()->saveMany($barcodeData);
        }
    }

    /**
     * @param array $ids
     * @param $warehouseId
     * @param $quantity
     * @return void
     */
    public function bulkUpdateQuantityReserved(array $ids, $warehouseId, $quantity): void
    {
        try {
            DB::transaction(static function () use ($ids, $warehouseId, $quantity){
                foreach ($ids as $id) {
                    ProductWarehouse::updateOrCreate([
                        'product_id' => $id,
                        'warehouse_id' => $warehouseId
                    ], [
                        'quantity_reserved' => $quantity
                    ]);
                }
            });
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    /**
     * @param Request $request
     * @param Customer|null $customer
     * @return array|Collection|\Illuminate\Database\Eloquent\Collection|Product[]
     */
    public function filterProducts(Request $request, Customer $customer = null)
    {
        $term = $request->get('term');
        $products = [];

        if ($term) {
            $term .= '%';

            if (is_null($customer)) {
                $customer = app('user')->getSessionCustomer();
            }

            $products = Product::where('customer_id', $customer->id)
                ->where(static function ($query) use ($term) {
                    return $query->where('sku', 'like', $term)
                        ->orWhere('name', 'like', $term);
                })->get();
        }

        return $products;
    }

    /**
     * @param Request $request
     * @param Product $product
     * @return JsonResponse
     */
    public function filterLots(Request $request, Product $product): JsonResponse
    {
        $lots = $product->lots();
        $results = [];

        $term = $request->get('q');

        if ($term) {
            $term .= '%';

            $lots->where('name','like', $term);
        }

        $lots = $lots->orderBy('created_at', 'desc')->pluck('name', 'id')->toArray();

        foreach ($lots as $id => $name) {
            $results[] = [
                'id' => $id,
                'text' => $name
            ];
        }

        return response()->json([
            'results' => $results
        ]);
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function existsAsComponent(Product $product): bool
    {
        return Product::whereHas('kitItems', fn($query) => $query->where('child_product_id', $product->id))->exists();
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function existsAsKit(Product $product): bool
    {
        return Product::whereHas('kitItems', fn($query) => $query->where('parent_product_id', $product->id))->exists();
    }

    /**
     * @param Product $product
     * @return void
     */
    public function syncKitProductWithOrderItems(Product $product): void
    {
        $orderItems = OrderItem::query()
            ->where('product_id', $product->id)
            ->where('quantity_pending', '>', 0)
            ->get();

        foreach ($orderItems as $orderItem) {
            app('order')->syncKitsWithOrderItem($orderItem);
        }
    }
}
