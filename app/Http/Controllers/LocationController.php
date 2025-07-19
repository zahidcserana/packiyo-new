<?php

namespace App\Http\Controllers;

use App\Components\ProductComponent;
use App\Http\Requests\Location\BulkDeleteRequest;
use App\Http\Requests\Product\AddToLocationRequest;
use Illuminate\Support\Facades\DB;
use App\Http\Dto\Filters\{LocationsDataTableDto, ProductLocationsDataTableDto};
use App\Http\Requests\Csv\{ExportCsvRequest, ImportCsvRequest};
use App\Http\Requests\Location\{DestroyRequest, StoreRequest, UpdateRequest};
use App\Http\Requests\LocationProduct\{ExportInventoryRequest, ImportInventoryRequest};
use App\Http\Resources\{LocationTableResource, ProductLocationTableResource};
use App\Models\{Customer, Location, LocationType, Product, Warehouse};
use Illuminate\Http\{JsonResponse, RedirectResponse, Request};
use Illuminate\Support\Facades\View;

class LocationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Location::class);
        $this->middleware('3pl')->only(['store', 'update', 'destroy']);
    }

    public function index()
    {
        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        $data = new LocationsDataTableDto(
            Warehouse::whereIn('customer_id', $customerIds)->get(),
            LocationType::whereIn('customer_id', $customerIds)->get(),
        );

        return view('locations.index', [
            'page' => 'locations',
            'data' => $data,
            'datatableOrder' => app('editColumn')->getDatatableOrder('locations'),
            'customer' => app('user')->getSelectedCustomers()
        ]);
    }

    public function dataTable(Request $request): JsonResponse
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnNames = 'locations.updated_at';
        $sortDirection = 'desc';

        $filterInputs = $request->get('filter_form');

        $customers = app('user')->getSelectedCustomers();

        if (!empty($columnOrder)) {
            $sortColumnNames = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $sortColumnNames = explode(',', $sortColumnNames);

        $locationCollection = app('location')->getQuery($customers, $filterInputs);

        if (!empty($request->get('from_date'))) {
            $locationCollection = $locationCollection->where('locations.updated_at', '>=', $request->get('from_date'));
        }

        foreach ($sortColumnNames as $sortColumnName) {
            $locationCollection = $locationCollection->orderBy(trim($sortColumnName), $sortDirection);
        }

        $term = $request->get('search')['value'];

        if ($term) {
            $locationCollection = app('location')->searchQuery($term, $locationCollection);
        }

        if ($request->get('length') && ((int) $request->get('length')) !== -1) {
            $locationCollection = $locationCollection->skip($request->get('start'))->limit($request->get('length'));
        }

        $locations = $locationCollection->get();
        $locationCollection = LocationTableResource::collection($locations);
        $visibleFields = app('editColumn')->getVisibleFields('locations');

        return response()->json([
            'data' => $locationCollection,
            'visibleFields' => $visibleFields,
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX
        ]);
    }

    public function productLocationDataTable(Request $request): JsonResponse
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'products.id';
        $sortDirection = 'desc';
        $filterInputs =  $request->get('filter_form');

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $productLocationCollection = app('location')->getProductLocationQuery($filterInputs, $sortColumnName, $sortDirection);

        if (!empty($request->get('from_date'))) {
            $productLocationCollection = $productLocationCollection->where('location_product.updated_at', '>=', $request->get('from_date'));
        }

        $term = $request->get('search')['value'];

        if ($term) {
            $productLocationCollection = app('location')->searchProductLocationQuery($term, $productLocationCollection);
        }

        if ($request->get('length') && ((int) $request->get('length')) !== -1) {
            $productLocationCollection = $productLocationCollection->skip($request->get('start'))->limit($request->get('length'));
        }

        $productLocationCollection = ProductLocationTableResource::collection($productLocationCollection->get());
        $visibleFields = app('editColumn')->getVisibleFields('location_product');

        return response()->json([
            'data' => $productLocationCollection,
            'visibleFields' => $visibleFields,
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX
        ]);
    }

    public function productLocations()
    {
        $customers = app('user')->getSelectedCustomers()->pluck('id')->toArray();
        $warehouses = Warehouse::whereIn('customer_id', $customers)->get();


        $data = new ProductLocationsDataTableDto(
            $warehouses,
        );

        return view('locations.productLocations', [
            'page' => 'productLocations',
            'data' => $data,
            'datatableOrder' => app('editColumn')->getDatatableOrder('location_product'),
        ]);
    }

    public function getEmptyLocations()
    {
        $productLocations = app()->location->getEmptyLocations();

        return response()->json([
            'success' => true,
            'data' => $productLocations->count()
        ]);
    }

    public function deleteEmptyLocations()
    {
        if (app()->location->deleteEmptyLocations()) {
            return response()->json([
                'success' => true,
                'message' => __('Empty locations successfully deleted.')
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('Something went wrong!')
        ]);
    }

    public function store(StoreRequest $request): JsonResponse
    {
        app('location')->store($request);

        return response()->json([
            'success' => true,
            'message' => __('Location successfully created.')
        ]);
    }

    public function update(UpdateRequest $request, Warehouse $warehouse, Location $location): JsonResponse
    {
        app('location')->update($request, $location);

        return response()->json([
            'success' => true,
            'message' => __('Location successfully updated.')
        ]);
    }

    public function destroy(Request $request, Warehouse $warehouse, Location $location): RedirectResponse
    {
        app('location')->destroy(DestroyRequest::make(['id' => $location->id]), $location);

        $routeName = $request->route()->getName();

        $data = ['warehouse' => $warehouse, 'location' => $location];

        if ($routeName === "warehouseLocation.destroy")
        {
            return redirect()->route('warehouses.editWarehouseLocation', $data)->withStatus(__('Warehouse location successfully deleted.'));
        }

        return redirect()->back()->withStatus('Location successfully deleted');
    }

    public function filterProducts(Request $request, Customer $customer)
    {
        return app('location')->filterProducts($request, $customer);
    }

    public function filterLocations(Request $request): JsonResponse
    {
        return app('location')->filterLocations($request);
    }

    public function getLocationModal(Location $location = null): string
    {
        return View::make('shared.modals.components.location.createEdit', [
            'location' => $location
        ])->render();
    }

    public function importInventory(ImportInventoryRequest $request): JsonResponse
    {
        app('location')->importInventory($request);

        return response()->json([
            'success' => true,
            'message' => __('CSV successfully imported')
        ]);
    }

    public function exportInventory(ExportInventoryRequest $request)
    {
        return app('location')->exportInventory($request);
    }

    public function importCsv(ImportCsvRequest $request): JsonResponse
    {
        $message = app('location')->importCsv($request);

        return response()->json([
            'success' => true,
            'message' => __($message)
        ]);
    }

    public function exportCsv(ExportCsvRequest $request)
    {
        return app('location')->exportCsv($request);
    }

    public function audit(Location $location)
    {
        return view('locations.audits', ['location' => $location]);
    }

    /**
     * @param BulkDeleteRequest $request
     * @return JsonResponse
     */
    public function bulkDelete(BulkDeleteRequest $request): JsonResponse
    {
        $notEmptyLocations = app('location')->bulkDelete($request);
        $message = __('Locations successfully deleted.');

        if (count($notEmptyLocations) > 0) {
            $message = __('Cannot delete following locations :locations, because inventory is assigned.', ['locations' => implode(', ', $notEmptyLocations)]);
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function adjustInventory(Request $request, int $locationId, int $productId, ProductComponent $productComponent): JsonResponse
    {
        $requestData = [
            'product_id' => $productId,
            'location_id' => $locationId,
            'lot_id' => null,
            'quantity' => (int) $request->input('quantity_on_hand', 0)
        ];

        $product = Product::query()->withTrashed()->findOrFail($productId, ['id', 'lot_tracking']);

        if ($product->lot_tracking) {
            $requestData['lot_id'] = (int) $request->input('lot_id');
        }

        $productComponent->addToLocation(AddToLocationRequest::make($requestData), $product);

        return response()->json([
            'success' => true,
            'message' => __('Product quantity successfully updated.')
        ]);
    }
}
