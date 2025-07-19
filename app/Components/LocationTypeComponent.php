<?php

namespace App\Components;

use App\Http\Requests\Csv\ExportCsvRequest;
use App\Http\Requests\Csv\ImportCsvRequest;
use App\Http\Resources\ExportResources\LocationTypeExportResource;
use App\Http\Requests\LocationType\{BulkDeleteRequest,
    DestroyBatchRequest,
    DestroyRequest,
    StoreBatchRequest,
    StoreRequest,
    UpdateRequest};
use App\Http\Requests\Task\UpdateBatchRequest;
use App\Http\Resources\LocationTypeCollection;
use App\Http\Resources\LocationTypeResource;
use App\Models\{Customer, Location, LocationType, Webhook};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LocationTypeComponent extends BaseComponent
{
    /**
     * @param StoreRequest $request
     * @param bool $fireWebhook
     * @return LocationType|Model
     */
    public function store(StoreRequest $request, bool $fireWebhook = true)
    {
        $input = $request->validated();

        if (intval($input['sellable']) == LocationType::SELLABLE_NOT_SET) {
            $input['sellable'] = null;
        }

        if (intval($input['pickable']) == LocationType::PICKABLE_NOT_SET) {
            $input['pickable'] = null;
        }

        if (isset($input['bulk_ship_pickable']) && intval($input['bulk_ship_pickable']) == LocationType::BULK_SHIP_PICKABLE_NOT_SET) {
            $input['bulk_ship_pickable'] = null;
        }

        if (isset($input['disabled_on_picking_app']) && intval($input['disabled_on_picking_app']) == LocationType::DISABLED_ON_PICKING_APP_NOT_SET) {
            $input['disabled_on_picking_app'] = null;
        }

        $locationType = LocationType::create($input);

        if ($fireWebhook) {
            $this->webhook(new LocationTypeResource($locationType), LocationType::class, Webhook::OPERATION_TYPE_STORE, $locationType->customer_id);
        }

        return $locationType;
    }

    /**
     * @param StoreBatchRequest $request
     * @return Collection
     */
    public function storeBatch(StoreBatchRequest $request): Collection
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $storeRequest = StoreRequest::make($record);
            $responseCollection->add($this->store($storeRequest));
        }

        $this->batchWebhook($responseCollection, LocationType::class, LocationTypeCollection::class, Webhook::OPERATION_TYPE_STORE);

        return $responseCollection;
    }

    /**
     * @param UpdateRequest $request
     * @param LocationType $locationType
     * @param bool $fireWebhook
     * @return LocationType
     */
    public function update(UpdateRequest $request, LocationType $locationType, bool $fireWebhook = true): LocationType
    {
        $input = $request->validated();

        if ($input['sellable'] == LocationType::SELLABLE_NOT_SET) {
            $input['sellable'] = null;
        }

        if ($input['pickable'] == LocationType::PICKABLE_NOT_SET) {
            $input['pickable'] = null;
        }

        if (isset($input['bulk_ship_pickable']) && $input['bulk_ship_pickable'] == LocationType::BULK_SHIP_PICKABLE_NOT_SET) {
            $input['bulk_ship_pickable'] = null;
        }

        if (isset($input['disabled_on_picking_app']) && $input['disabled_on_picking_app'] == LocationType::DISABLED_ON_PICKING_APP_NOT_SET) {
            $input['disabled_on_picking_app'] = null;
        }

        $locationType->update($input);

        if ($fireWebhook) {
            $this->webhook(new LocationTypeResource($locationType), LocationType::class, Webhook::OPERATION_TYPE_UPDATE, $locationType->customer_id);
        }

        return $locationType;
    }

    /**
     * @param UpdateBatchRequest $request
     * @return Collection
     */
    public function updateBatch(UpdateBatchRequest $request): Collection
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $updateRequest = UpdateRequest::make($record);
            $orderStatus = LocationType::find($record['id']);

            $responseCollection->add($this->update($updateRequest, $orderStatus, false));
        }

        $this->batchWebhook($responseCollection, LocationType::class, LocationTypeCollection::class, Webhook::OPERATION_TYPE_UPDATE);

        return $responseCollection;
    }

    /**
     * @param DestroyRequest $request
     * @param LocationType $locationType
     * @param bool $fireWebhook
     * @return array
     */
    public function destroy(DestroyRequest $request, LocationType $locationType, bool $fireWebhook = true): array
    {
        $request->validated();

        $locations = Location::whereLocationTypeId($locationType->id)->get();

        foreach ($locations as $location) {
            $location->location_type_id = null;
            $location->saveQuietly();
        }

        $locationType->delete();

        $response = ['id' => $locationType->id, 'customer_id' => $locationType->customer_id];

        if ($fireWebhook) {
            $this->webhook($response, LocationType::class, Webhook::OPERATION_TYPE_DESTROY, $locationType->customer_id);
        }

        return $response;
    }

    /**
     * @param DestroyBatchRequest $request
     * @return Collection
     */
    public function destroyBatch(DestroyBatchRequest $request): Collection
    {
        $responseCollection = new Collection();
        $input = $request->validated();

        foreach ($input as $record) {
            $destroyRequest = DestroyRequest::make($record);
            $locationType = LocationType::find($record['id']);

            $responseCollection->add($this->destroy($destroyRequest, $locationType, false));
        }

        $this->batchWebhook($responseCollection, LocationType::class, ResourceCollection::class, Webhook::OPERATION_TYPE_DESTROY);

        return $responseCollection;
    }

    /**
     * @param Request $request
     * @param Customer|null $customer
     * @return JsonResponse
     */
    public function getTypes(Request $request, Customer $customer = null): JsonResponse
    {
        $term = $request->get('term');
        $results = [];

        if (is_null($customer)) {
            $customers = app('user')->getSelectedCustomers()->pluck('id')->toArray();
        } else {
            $customers = [$customer->id];
        }

        if ($term) {
            $term = $term . '%';

            $locationTypes = LocationType::whereIn('customer_id', $customers)->where('name', 'like', $term)->get();

        } else {
            $locationTypes = LocationType::whereIn('customer_id', $customers)->get();
        }

        foreach ($locationTypes as $locationType) {
            if ($locationType->count()) {
                $results[] = [
                    'id' => $locationType->id,
                    'text' => $locationType->name
                ];
            }
        }

        return response()->json([
            'results' => $results
        ]);
    }

    /**
     * @param string $term
     * @param $locationTypesCollection
     * @return mixed
     */
    public function searchQuery(string $term, $locationTypesCollection): mixed
    {
        $term .= '%';

        return $locationTypesCollection->where(function (Builder $q) use ($term) {
            $q->where('location_types.name', 'like', $term)
                ->orWhereHas('customer.contactInformation', function ($q) use ($term) {
                    $q->where('name', 'like', $term);
                });
        });
    }

    /**
     * @param string $sortColumnName
     * @param string $sortDirection
     * @return Builder
     */
    public function getQuery(string $sortColumnName = 'location_types.name', string $sortDirection = 'asc'): Builder
    {
        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        return LocationType::query()
            ->join('customers', 'location_types.customer_id', '=', 'customers.id')
            ->join('contact_informations AS customer_contact_information', 'customers.id', '=', 'customer_contact_information.object_id')
            ->where('customer_contact_information.object_type', Customer::class)
            ->whereIn('customer_id', $customerIds)
            ->groupBy('location_types.id')
            ->select('location_types.*')
            ->orderBy($sortColumnName, $sortDirection);
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
            LocationTypeExportResource::columns()
        );

        if (!empty($importLines)) {
            $storedCollection = new Collection();
            $updatedCollection = new Collection();

            $locationTypesToImport = [];

            foreach ($importLines as $importLine) {
                $data = [];
                $data['customer_id'] = $input['customer_id'];

                foreach ($columns as $columnsIndex => $column) {
                    if (Arr::has($importLine, $columnsIndex)) {
                        $data[$column] = Arr::get($importLine, $columnsIndex);
                    }
                }

                if (!Arr::has($locationTypesToImport, $data['name'])) {
                    $locationTypesToImport[$data['name']] = [];
                }

                $locationTypesToImport[$data['name']][] = $data;
            }

            $locationTypeToImportIndex = 0;

            foreach ($locationTypesToImport as $locationTypeToImport) {
                $locationType = LocationType::query()
                    ->where('name', $locationTypeToImport[0]['name'])
                    ->where('customer_id', $locationTypeToImport[0]['customer_id'])
                    ->first();

                if ($locationType) {
                    $updatedCollection->add($this->update($this->createRequestFromImport($locationTypeToImport, $locationType, true), $locationType, false));
                } else {
                    $storedCollection->add($this->store($this->createRequestFromImport($locationTypeToImport)));
                }

                Session::flash('status', ['type' => 'info', 'message' => __('Importing :current/:total Location Types', ['current' => ++$locationTypeToImportIndex, 'total' => count($locationTypesToImport)])]);
                Session::save();
            }

            $this->batchWebhook($storedCollection, LocationType::class, LocationTypeCollection::class, Webhook::OPERATION_TYPE_STORE);
            $this->batchWebhook($updatedCollection, LocationType::class, LocationTypeCollection::class, Webhook::OPERATION_TYPE_UPDATE);
        }

        Session::flash('status', ['type' => 'success', 'message' => __('Location Types were successfully imported!')]);

        return __('Location Types were successfully imported!');
    }

    /**
     * @param array $data
     * @param LocationType|null $locationType
     * @param bool $update
     * @return StoreRequest|UpdateRequest
     */
    private function createRequestFromImport(array $data, LocationType $locationType = null, bool $update = false): UpdateRequest|StoreRequest
    {
        $requestData = [
            'customer_id' => $data[0]['customer_id'],
            'name' => $data[0]['name'],
            'pickable' => strtolower($data[0]['pickable']) == 'yes' ? 1 : 0,
            'sellable' => strtolower($data[0]['sellable']) == 'yes' ? 1 : 0,
            'disabled_on_picking_app' => strtolower(Arr::get($data, '0.disabled_on_picking_app')) == 'yes' ? 1 : 0,
        ];

        if ($update) {
            $requestDataID = [
                'id' => $locationType->id
            ];
            $requestData = array_merge($requestDataID, $requestData);
        }

        return $update ? UpdateRequest::make($requestData) : StoreRequest::make($requestData);
    }

    /**
     * @param ExportCsvRequest $request
     * @return StreamedResponse
     */
    public function exportCsv(ExportCsvRequest $request): StreamedResponse
    {
        $input = $request->validated();
        $search = $input['search']['value'];

        $locationTypesCollection = $this->getQuery();

        if ($search) {
            $locationTypesCollection = $this->searchQuery($search, $locationTypesCollection);
        }

        $csvFileName = Str::kebab(auth()->user()->contactInformation->name) . '-location-types-export.csv';

        return app('csv')->export($request, $locationTypesCollection->get(), LocationTypeExportResource::columns(), $csvFileName, LocationTypeExportResource::class);
    }

    /**
     * @param BulkDeleteRequest $request
     * @return array
     */
    public function bulkDelete(BulkDeleteRequest $request): array
    {
        $input = $request->validated();
        $notEmptyLocationTypes = [];

        foreach ($input['ids'] as $locationTypeId) {
            $locationType = LocationType::findOrFail($locationTypeId);

            if ($locationType) {
                if ($locationType->locations()->exists()) {
                    $notEmptyLocationTypes[] = $locationType->name;
                } else {
                    $locationType->delete();
                }
            }
        }

        return $notEmptyLocationTypes;
    }
}
