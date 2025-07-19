<?php

namespace App\Components;

use App\Http\Requests\Csv\{ExportCsvRequest, ImportCsvRequest};
use App\Http\Requests\LocationType\StoreRequest as LocationTypeStoreRequest;
use App\Http\Requests\Location\{BulkDeleteRequest,
    DestroyBatchRequest,
    DestroyRequest,
    StoreBatchRequest,
    StoreRequest,
    UpdateBatchRequest,
    UpdateRequest};
use App\Http\Requests\LocationProduct\{ExportInventoryRequest, ImportInventoryRequest};
use App\Http\Resources\{ExportResources\InventoryExportResource,
    ExportResources\LocationExportResource,
    LocationCollection,
    LocationResource};
use App\Models\{Customer, Location, LocationType, LocationProduct, Product, Warehouse, Webhook};
use Illuminate\Database\{Eloquent\Builder, Query\JoinClause};
use Illuminate\Http\{JsonResponse, Request, Resources\Json\ResourceCollection};
use Illuminate\Support\{Arr, Collection, Facades\Session, Str};
use PHPUnit\Exception;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LocationComponent extends BaseComponent
{
    public function __construct()
    {
    }

    public function store(StoreRequest $request, $fireWebhook = true)
    {
        $input = $request->validated();

        $location = Location::create($input);

        if ($fireWebhook) {
            $this->webhook(new LocationResource($location), Location::class, Webhook::OPERATION_TYPE_STORE, $location->warehouse->customer_id);
        }

        return $location;
    }

    public function storeBatch(StoreBatchRequest $request): Collection
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $storeRequest = StoreRequest::make($record);
            $responseCollection->add($this->store($storeRequest, false));
        }

        $this->batchWebhook($responseCollection, Location::class, LocationCollection::class, Webhook::OPERATION_TYPE_STORE);

        return $responseCollection;
    }

    public function update(UpdateRequest $request, Location $location, $fireWebhook = true): Location
    {
        $input = $request->validated();

        if (isset($input['priority_counting_requested_at']) && !$location->priority_counting_requested_at) {
            $input['priority_counting_requested_at'] = now();
        } else {
            $input['priority_counting_requested_at'] = null;
        }

        $location->update($input);

        if ($fireWebhook) {
            $this->webhook(new LocationResource($location), Location::class, Webhook::OPERATION_TYPE_UPDATE, $location->warehouse->customer_id);
        }

        return $location;
    }

    public function updateBatch(UpdateBatchRequest $request): Collection
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $updateRequest = UpdateRequest::make($record);
            $location = Location::where('id', $record['id'])->where('warehouse_id', $record['warehouse_id'])->first();

            $responseCollection->add($this->update($updateRequest, $location, false));
        }

        $this->batchWebhook($responseCollection, Location::class, LocationCollection::class, Webhook::OPERATION_TYPE_UPDATE);

        return $responseCollection;
    }

    public function destroy(?DestroyRequest $request, Location $location): array
    {
        foreach ($location->products as $locationProduct) {
            app('inventoryLog')->adjustInventory(
                $location,
                $locationProduct,
                0,
                InventoryLogComponent::OPERATION_TYPE_MANUAL
            );
        }

        $location->delete();

        return ['name' => $location->name, 'customer_id' => $location->warehouse->customer_id];
    }

    public function destroyBatch(DestroyBatchRequest $request): Collection
    {
        $responseCollection = new Collection();
        $input = $request->validated();

        foreach ($input as $record) {
            $destroyRequest = DestroyRequest::make($record);
            $location = Location::where('id', $record['id'])->first();

            $responseCollection->add($this->destroy($destroyRequest, $location, false));
        }

        $this->batchWebhook($responseCollection, Location::class, ResourceCollection::class, Webhook::OPERATION_TYPE_DESTROY);

        return $responseCollection;
    }

    public function filterProducts(Request $request, Customer $customer)
    {
        $term = $request->get('term');

        $results = [];
        $productIds = [];
        $customers = new Collection();

        if ($request->get('customer_id')) {
            $customers = Collection::make(Customer::find($request->get('customer_id'))->get());
        }

        if ($customers->isEmpty()) {
            $customers = app('user')->getSelectedCustomers();
        }

        if ($customers->isNotEmpty() && $term) {
            $term = $term . '%';
            $products = Product::whereIn('customer_id', $customers->pluck('id')->toArray())
                ->where(static function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term);
                })
                ->get();


            foreach ($products as $product) {
                $results[] = [
                    'id' => $product->id,
                    'text' => 'SKU: ' . $product->sku . ', NAME:' . $product->name
                ];
            }

            return response()->json([
                'results' => $results
            ]);
        }

        return response()->json([
            'results' => []
        ]);
    }

    public function filterLocations(Request $request): JsonResponse
    {
        $term = $request->get('term');
        $product = $request->get('product_id');
        $results = [];

        $customers = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        if ($term) {
            $term .= '%';

            $locations = Location::join('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
                ->join('contact_informations AS warehouse_contact_information', 'warehouses.id', '=', 'warehouse_contact_information.object_id')
                ->where('warehouse_contact_information.object_type', Warehouse::class)
                ->whereIn('warehouses.customer_id', $customers)
                ->where(function ($query) use ($term) {
                    $query->where('locations.name', 'like', $term);
                })
                ->select('locations.*', 'warehouse_contact_information.name AS warehouseName')
                ->get();

            foreach ($locations as $location) {
                $results[] = [
                    'id' => $location->id,
                    'text' => __(':locationName (:warehouseName), pickable - :pickable, sellable - :sellable', [
                            'locationName' => $location->name,
                            'pickable' => $location->is_pickable_label,
                            'sellable' => $location->is_sellable_label,
                            'warehouseName' => $location->warehouseName
                        ])
                ];
            }
        }

        return response()->json([
            'results' => $results
        ]);
    }

    /**
     * @param ImportInventoryRequest $request
     * @return string
     */
    public function importInventory(ImportInventoryRequest $request): string
    {
        $input = $request->validated();
        $messages = [];

        $warehouse = Warehouse::find($input['warehouse_id']);
        $customer = Customer::find($input['customer_id']);

        $importLines = app('csv')->getCsvData($input['inventory_csv']);

        $columns = array_intersect(
            app('csv')->unsetCsvHeader($importLines, 'sku'),
            InventoryExportResource::columns()
        );

        $importLineIndex = 0;

        if (!empty($importLines)) {
            foreach ($importLines as $importLineIndex => $importLine) {
                $data = [];
                $lot = null;

                foreach ($columns as $columnIndex => $column) {
                    if (Arr::has($importLine, $columnIndex)) {
                        $data[$column] = Arr::get($importLine, $columnIndex);
                    }
                }

                $product = $customer->products()->where('sku', $data['sku'])->first();
                $location = $warehouse->locations()->where('name', $data['location'])->first();
                $quantity = (int)$data['quantity'];

                if ($product && $location) {
                    $action = strtolower($data['action (replace, increase, decrease)']);

                    if ($product->lot_tracking) {
                        $lot = $product->lots()
                            ->where('name', $data['lot_name'])
                            ->whereHas('placedLotItems', fn(Builder $query) => $query->where('location_id', $location->id))
                            ->first();
                    }

                    app('inventoryLog')->adjustInventory(
                        $location,
                        $product,
                        $quantity,
                        $action === 'replace' ? InventoryLogComponent::OPERATION_TYPE_MANUAL : $action,
                        null,
                        $lot
                    );
                }
            }

            Session::flash('status', [
                'type' => 'info',
                'message' => __('Importing :current/:total inventory lines', [
                    'current' => $importLineIndex + 1,
                    'total' => count($importLines)
                ])
            ]);

            Session::save();
        }

        Session::flash('status', $messages);

        return __('Inventory was successfully imported!');
    }

    /**
     * @param ExportInventoryRequest $request
     * @return StreamedResponse
     */
    public function exportInventory(ExportInventoryRequest $request): StreamedResponse
    {
        $input = $request->validated();
        $search = $input['search']['value'];

        $locationProducts = $this->getProductLocationQuery($request->get('filter_form'));

        if ($search) {
            $locationProducts = $this->searchProductLocationQuery($search, $locationProducts);
        }

        $csvFileName = Str::kebab(auth()->user()->contactInformation->name) . '-inventory-export.csv';

        return app('csv')->export($request, $locationProducts->get(), InventoryExportResource::columns(), $csvFileName, InventoryExportResource::class);
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
            app('csv')->unsetCsvHeader($importLines, 'name'),
            LocationExportResource::columns()
        );

        if (!empty($importLines)) {
            $storedCollection = new Collection();
            $updatedCollection = new Collection();

            $locationsToImport = [];

            foreach ($importLines as $importLine) {
                $data = [];
                $data['customer_id'] = $input['customer_id'];

                foreach ($columns as $columnsIndex => $column) {
                    if (Arr::has($importLine, $columnsIndex)) {
                        $data[$column] = Arr::get($importLine, $columnsIndex);
                    }
                }

                if (!Arr::has($locationsToImport, $data['name'])) {
                    $locationsToImport[$data['name']] = [];
                }

                $locationsToImport[$data['name']][] = $data;
            }

            $locationToImportIndex = 0;

            foreach ($locationsToImport as $locationToImport) {
                $warehouse = Warehouse::with([
                    'locations' => function($query) use ($locationToImport) {
                        $query->where('name', $locationToImport[0]['name']);
                    },
                    'contactInformation',
                    'customer'
                ])->whereHas('contactInformation', static function($query) use ($locationToImport) {
                    $query->where('name', $locationToImport[0]['warehouse']);
                })
                ->where('customer_id', $locationToImport[0]['customer_id'])->first();

                if($warehouse) {
                    $location = $warehouse->locations->first();
                    $locationToImport[0]['warehouse_id'] = $warehouse->id;

                    if ($location) {
                        $updatedCollection->add($this->update($this->createRequestFromImport($locationToImport, $location, true), $location,false));
                    } else {
                        $storedCollection->add($this->store($this->createRequestFromImport($locationToImport), $location));
                    }

                    Session::flash('status', ['type' => 'info', 'message' => __('Importing :current/:total locations', ['current' => ++$locationToImportIndex , 'total' => count($locationsToImport)])]);
                    Session::save();
                }
            }

            $this->batchWebhook($storedCollection, Location::class, LocationCollection::class, Webhook::OPERATION_TYPE_STORE);
            $this->batchWebhook($updatedCollection, Location::class, LocationCollection::class, Webhook::OPERATION_TYPE_UPDATE);
        }

        Session::flash('status', ['type' => 'success', 'message' => __('Locations were successfully imported!')]);

        return __('Locations were successfully imported!');
    }

    /**
     * @param ExportCsvRequest $request
     * @return StreamedResponse
     */
    public function exportCsv(ExportCsvRequest $request): StreamedResponse
    {
        $input = $request->validated();
        $search = $input['search']['value'];

        $customers = app('user')->getSelectedCustomers();

        $locations = $this->getQuery($customers, $request->get('filter_form'));

        if ($search) {
            $locations = $this->searchQuery($search, $locations);
        }

        $csvFileName = Str::kebab(auth()->user()->contactInformation->name) . '-locations-export.csv';

        return app('csv')->export($request, $locations->get(), LocationExportResource::columns(), $csvFileName, LocationExportResource::class);
    }

    /**
     * @param $filterInputs
     * @param string $sortColumnName
     * @param string $sortDirection
     * @param $customers
     * @return mixed
     */
    public function getProductLocationQuery($filterInputs, string $sortColumnName = 'products.id', string $sortDirection = 'desc')
    {
        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        $filterCustomerId = Arr::get($filterInputs, 'customer_id');

        if ($filterCustomerId && $filterCustomerId != 'all') {
            $customerIds = array_intersect($customerIds, [$filterCustomerId]);
        }

        $productLocationCollection = LocationProduct::join('products', 'products.id', '=', 'location_product.product_id')
            ->join('locations', 'locations.id', '=', 'location_product.location_id')
            ->leftJoin('lot_items', static function (JoinClause $joinClause) {
                $joinClause->on('location_product.location_id', 'lot_items.location_id')
                    ->on('location_product.product_id', 'lot_items.product_id')
                    ->where('lot_items.quantity_remaining', '>', 0)
                    ->whereNull('lot_items.deleted_at');
            })
            ->leftJoin('lots', static function (JoinClause $joinClause) {
                $joinClause->on('lot_items.lot_id', 'lots.id');
            })
            ->whereIn('products.customer_id', $customerIds)
            ->where(function ($query) use ($filterInputs) {
                // Find by filter result
                // Warehouse
                if ($filterInputs['warehouse']) {
                    $query->where('locations.warehouse_id', $filterInputs['warehouse']);
                }

                // Sellable
                if (isset($filterInputs['sellable'])) {
                    $query->where('locations.sellable', $filterInputs['sellable']);
                }

                // Pickable
                if (isset($filterInputs['pickable'])) {
                    $query->where('locations.pickable', $filterInputs['pickable']);
                }

                if (!empty($filterInputs['exclude_empty'])) {
                    $query->where('location_product.quantity_on_hand', '>', 0);
                }
            })
            ->select([
                'location_product.*',
                'products.sku',
                'products.name',
                'lots.id AS lot_id',
                'lots.name AS lot_name',
                'lots.expiration_date as lot_expiration_date',
                'lot_items.id AS lot_item_id',
                'lot_items.quantity_remaining as lot_item_quantity_remaining',
            ])
            ->orderBy($sortColumnName, $sortDirection);

        return $productLocationCollection;
    }

    public function getEmptyLocations()
    {
        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        $productLocationCollection = LocationProduct::join('products', 'products.id', '=', 'location_product.product_id')
            ->whereIn('products.customer_id', $customerIds)
            ->where('location_product.quantity_on_hand', '<=', 0);

        return $productLocationCollection;
    }

    public function deleteEmptyLocations()
    {
        return $this->getEmptyLocations()->delete();
    }

    /**
     * @param string $term
     * @param $productLocationCollection
     * @return mixed
     */
    public function searchProductLocationQuery(string $term, $productLocationCollection)
    {
        $term = $term . '%';

        return $productLocationCollection->where(static function(Builder $query) use ($term) {
            $query->orWhere('locations.name', 'like', $term)
                ->orWhere('products.name', 'like', $term)
                ->orWhere('products.sku', 'like', $term);
        });
    }

    public function searchQuery(string $term, $locationCollection)
    {
        $term = $term . '%';

        return $locationCollection->where(function ($q) use ($term) {
            $q->whereHas('warehouse.contactInformation', function($query) use ($term) {
                $query->where('name', 'like', $term)
                    ->orWhere('address', 'like', $term)
                    ->orWhere('city', 'like', $term)
                    ->orWhere('zip', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            })
            ->orWhere('locations.name', 'like', $term);
        });
    }

    public function getQuery($customers, $filterInputs)
    {
        $customers = $customers->pluck('id')->toArray();

        $locationCollection = Location::join('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->join('contact_informations', 'warehouses.id', '=', 'contact_informations.object_id')
            ->leftJoin('location_types', 'locations.location_type_id', '=', 'location_types.id')
            ->where('contact_informations.object_type', Warehouse::class)
            ->whereIn('warehouses.customer_id', $customers)
            ->where(function ($query) use ($filterInputs) {
                // Find by filter result
                // Warehouse
                if (Arr::get($filterInputs, 'warehouse')) {
                    $query->where('locations.warehouse_id', $filterInputs['warehouse']);
                }

                // Location type
                if (Arr::get($filterInputs, 'location_type')) {
                    $query->where('locations.location_type_id', $filterInputs['location_type']);
                }

                // Sellable
                $sellable = Arr::get($filterInputs, 'sellable');
                if (!is_null($sellable)) {
                    $query->where('locations.sellable', $sellable);
                }

                // Pickable
                $pickable = Arr::get($filterInputs, 'pickable');
                if (!is_null($pickable)) {
                    $query->where('locations.pickable', $pickable);
                }

                if (!is_null($filterInputs) && !empty($filterInputs['customer']) && $filterInputs['customer'] !== 0) {
                    $query->where('warehouses.customer_id', $filterInputs['customer']);
                }
            })
            ->select('locations.*')
            ->groupBy('locations.id');

        return $locationCollection;
    }

    /**
     * @param array $data
     * @param Location|null $location
     * @param bool $update
     * @return StoreRequest|UpdateRequest
     */
    private function createRequestFromImport(array $data, Location $location = null, bool $update = false): StoreRequest|UpdateRequest
    {
        $requestData = [
            'warehouse_id' => Arr::get($data, '0.warehouse_id'),
            'name' => Arr::get($data, '0.name')
        ];

        $barcode = Arr::get($data, '0.barcode');

        if ($barcode) {
            $requestData['barcode'] = $barcode;
        }

        $flagAttributes = [
            'pickable',
            'sellable',
            'disabled_on_picking_app',
            'bulk_ship_pickable',
            'is_receiving'
        ];

        foreach ($flagAttributes as $flagAttribute) {
            if (Arr::has($data, '0.' . $flagAttribute)) {
                $requestData[$flagAttribute] = strtolower(Arr::get($data, '0.' . $flagAttribute)) === 'yes';
            }
        }

        if (Arr::has($data, '0.type')) {
            $locationTypeId = null;
            if ($locationTypeName = Arr::get($data, '0.type')) {
                $locationType = LocationType::where('name', $locationTypeName)
                    ->where('customer_id', Arr::get($data, '0.customer_id'))
                    ->first();

                if (!$locationType) {
                    $locationTypeInput = [
                        'customer_id' => Arr::get($data, '0.customer_id'),
                        'name' => $locationTypeName,
                        'pickable' => Arr::get($requestData, 'pickable'),
                        'sellable' => Arr::get($requestData, 'sellable'),
                        'disabled_on_picking_app' => Arr::get($requestData, 'disabled_on_picking_app'),
                        'bulk_ship_pickable' => Arr::get($requestData, 'bulk_ship_pickable')
                    ];

                    $locationType = app('locationType')->store(LocationTypeStoreRequest::make($locationTypeInput));
                }

                $locationTypeId = $locationType->id;
            }

            $requestData['location_type_id'] = $locationTypeId;
        }

        if ($update) {
            $requestData['id'] = $location->id;
        }

        return $update ? UpdateRequest::make($requestData) : StoreRequest::make($requestData);
    }

    /**
     * @param BulkDeleteRequest $request
     * @return array
     */
    public function bulkDelete(BulkDeleteRequest $request): array
    {
        $input = $request->validated();
        $notEmptyLocations = [];

        foreach ($input['ids'] as $locationId) {
            $location = Location::findOrFail($locationId);

            if ($location) {
                if ($location->products()->exists()) {
                    $notEmptyLocations[] = $location->name;
                } else {
                    $location->delete();
                }
            }
        }

        return $notEmptyLocations;
    }
}
