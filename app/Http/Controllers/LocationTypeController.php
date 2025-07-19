<?php

namespace App\Http\Controllers;

use App\Http\Requests\Csv\ExportCsvRequest;
use App\Http\Requests\Csv\ImportCsvRequest;
use App\Http\Requests\LocationType\{BulkDeleteRequest, DestroyRequest, StoreRequest, UpdateRequest};
use App\Http\Resources\LocationTypeTableResource;
use App\Models\{Customer, LocationType};
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Illuminate\Http\{JsonResponse, RedirectResponse, Request};

class LocationTypeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(LocationType::class);
    }

    public function index()
    {
        return view('location_types.index', [
            'page' => 'locations',
            'datatableOrder' => app()->editColumn->getDatatableOrder('location-types'),
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function dataTable(Request $request): JsonResponse
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'location_types.name';
        $sortDirection = 'asc';
        $term = $request->get('search')['value'];

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'] ?? $sortColumnName;
            $sortDirection = $columnOrder[0]['dir'] ?? $sortDirection;
        }

        $locationTypesCollection = app('locationType')->getQuery($sortColumnName, $sortDirection);

        if ($term) {
            $locationTypesCollection = app('locationType')->searchQuery($term, $locationTypesCollection);
        }

        if ($request->get('length') && ((int) $request->get('length')) !== -1) {
            $locationTypesCollection = $locationTypesCollection->skip($request->get('start'))->limit($request->get('length'));
        }

        $locationTypes = $locationTypesCollection->get();
        $locationTypesCollection = LocationTypeTableResource::collection($locationTypes);

        return response()->json([
            'data' => $locationTypesCollection,
            'visibleFields' => app()->editColumn->getVisibleFields('location-types'),
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX
        ]);
    }

    public function create()
    {
        return view('location_types.create');
    }

    /**
     * @param StoreRequest $request
     * @return mixed
     */
    public function store(StoreRequest $request)
    {
        app()->locationType->store($request);

        return redirect()->route('location_type.index')->withStatus(__('Location type was successfully added.'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param LocationType $locationType
     * @return Factory|View
     */
    public function edit(LocationType $locationType)
    {
        return view('location_types.edit', compact('locationType'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateRequest $request
     * @param LocationType $locationType
     * @return RedirectResponse
     */
    public function update(UpdateRequest $request, LocationType $locationType): RedirectResponse
    {
        app()->locationType->update($request, $locationType);

        return redirect()->route('location_type.index')->withStatus(__('Location type successfully updated.'));
    }

    /**
     * @param DestroyRequest $request
     * @param LocationType $location_type
     * @return mixed
     */
    public function destroy(DestroyRequest $request, LocationType $location_type)
    {
        app()->locationType->destroy($request, $location_type);

        return redirect()->back()->withStatus(__('Location type was successfully deleted.'));
    }

    /**
     * @param Request $request
     * @param Customer|null $customer
     * @return mixed
     */
    public function getTypes(Request $request, Customer $customer = null)
    {
        return app()->locationType->getTypes($request, $customer);
    }

    /**
     * @param ImportCsvRequest $request
     * @return JsonResponse
     */
    public function importCsv(ImportCsvRequest $request): JsonResponse
    {
        $message = app('locationType')->importCsv($request);

        return response()->json(['success' => true, 'message' => __($message)]);
    }

    /**
     * @param ExportCsvRequest $request
     * @return mixed
     */
    public function exportCsv(ExportCsvRequest $request)
    {
        return app('locationType')->exportCsv($request);
    }

    /**
     * @param BulkDeleteRequest $request
     * @return JsonResponse
     */
    public function bulkDelete(BulkDeleteRequest $request): JsonResponse
    {
        $notEmptyLocationTypes = app('locationType')->bulkDelete($request);
        $message = __('Location types successfully deleted.');

        if (count($notEmptyLocationTypes) > 0) {
            $message = __('Cannot delete following location types :locations, because there are assigned locations.', ['locations' => implode(', ', $notEmptyLocationTypes)]);
        }

        return response()->json(['success' => true, 'message' => $message]);
    }
}
