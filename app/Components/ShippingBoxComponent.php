<?php

namespace App\Components;

use App\Http\Requests\Csv\ExportCsvRequest;
use App\Http\Requests\Csv\ImportCsvRequest;
use App\Http\Requests\ShippingBox\DestroyBatchRequest;
use App\Http\Requests\ShippingBox\DestroyRequest;
use App\Http\Requests\ShippingBox\StoreBatchRequest;
use App\Http\Requests\ShippingBox\StoreRequest;
use App\Http\Requests\ShippingBox\UpdateBatchRequest;
use App\Http\Requests\ShippingBox\UpdateRequest;
use App\Http\Resources\ExportResources\ShippingBoxExportResource;
use App\Http\Resources\ShippingBoxCollection;
use App\Http\Resources\ShippingBoxResource;
use App\Models\ShippingBox;
use App\Models\Webhook;
use App\Models\Customer;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShippingBoxComponent extends BaseComponent
{
    public function store(StoreRequest $request, $fireWebhook = true)
    {
        $input = $request->validated();

        $shippingBox = ShippingBox::create($input);

        if ($fireWebhook == true) {
            $this->webhook(new ShippingBoxResource
            ($shippingBox), ShippingBox::class, Webhook::OPERATION_TYPE_STORE, $shippingBox->customer_id);
        }

        return $shippingBox;
    }

    public function storeBatch(StoreBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $storeRequest = StoreRequest::make($record);
            $responseCollection->add($this->store($storeRequest, false));
        }

        $this->batchWebhook($responseCollection, ShippingBox::class, ShippingBoxCollection::class, Webhook::OPERATION_TYPE_STORE);

        return $responseCollection;
    }

    public function update(UpdateRequest $request, ShippingBox $shippingBox, $fireWebhook = true)
    {
        $input = $request->validated();

        $shippingBox->update($input);

        if ($fireWebhook == true) {
            $this->webhook(new ShippingBoxResource($shippingBox), ShippingBox::class, Webhook::OPERATION_TYPE_UPDATE, $shippingBox->customer_id);
        }

        return $shippingBox;
    }

    public function updateBatch(UpdateBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $updateRequest = UpdateRequest::make($record);
            $shippingBox = ShippingBox::find($record['id']);

            $responseCollection->add($this->update($updateRequest, $shippingBox, false));
        }

        $this->batchWebhook($responseCollection, ShippingBox::class, ShippingBoxCollection::class, Webhook::OPERATION_TYPE_UPDATE);

        return $responseCollection;
    }

    public function destroy(DestroyRequest $request, ShippingBox $shippingBox, $fireWebhook = true)
    {
        $shippingBox->delete();

        $response = ['id' => $shippingBox->id, 'customer_id' => $shippingBox->customer_id];

        if ($fireWebhook == true) {
            $this->webhook($response, ShippingBox::class, Webhook::OPERATION_TYPE_DESTROY, $shippingBox->customer_id);
        }

        return $response;
    }

    public function destroyBatch(DestroyBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $destroyRequest = DestroyRequest::make($record);
            $shippingBox = ShippingBox::find($record['id']);

            $responseCollection->add($this->destroy($destroyRequest, $shippingBox, false));
        }

        $this->batchWebhook($responseCollection, ShippingBox::class, ResourceCollection::class, Webhook::OPERATION_TYPE_DESTROY);

        return $responseCollection;
    }

    public function filterShippingBoxes(Customer $customer = null): Collection
    {
        if (is_null($customer)) {
            return collect([]);
        }

        $shippingBoxQuery = ShippingBox::query();

        $customers[] = $customer->id;

        if ($customer->parent) {
            $customers[] = $customer->parent_id;
        }

        $shippingBoxQuery = $shippingBoxQuery->whereIn('customer_id', $customers);

        return $shippingBoxQuery->get();
    }

    /**
     * @param string $term
     * @param $shippingBoxCollection
     * @return mixed
     */
    public function searchQuery(string $term, $shippingBoxCollection): mixed
    {
        return $shippingBoxCollection->where('name', 'like', $term . '%');
    }

    /**
     * @param string $sortColumnName
     * @param string $sortDirection
     * @return mixed
     */
    public function getQuery(string $sortColumnName = 'shipping_boxes.name', string $sortDirection = 'asc'): mixed
    {
        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        return ShippingBox::query()
            ->whereIn('customer_id', $customerIds)
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
            ShippingBoxExportResource::columns()
        );

        if (!empty($importLines)) {
            $storedCollection = new Collection();
            $updatedCollection = new Collection();

            $shippingBoxesToImport = [];

            foreach ($importLines as $importLine) {
                $data = [];
                $data['customer_id'] = $input['customer_id'];

                foreach ($columns as $columnsIndex => $column) {
                    if (Arr::has($importLine, $columnsIndex)) {
                        $data[$column] = Arr::get($importLine, $columnsIndex);
                    }
                }

                if (!Arr::has($shippingBoxesToImport, $data['name'])) {
                    $shippingBoxesToImport[$data['name']] = [];
                }

                $shippingBoxesToImport[$data['name']][] = $data;
            }

            $shippingBoxToImportIndex = 0;

            foreach ($shippingBoxesToImport as $shippingBoxToImport) {
                $shippingBox = ShippingBox::query()
                    ->where('name', $shippingBoxToImport[0]['name'])
                    ->where('customer_id', $shippingBoxToImport[0]['customer_id'])
                    ->first();

                if ($shippingBox) {
                    $updatedCollection->add($this->update($this->createRequestFromImport($shippingBoxToImport, $shippingBox, true), $shippingBox, false));
                } else {
                    $storedCollection->add($this->store($this->createRequestFromImport($shippingBoxToImport), $shippingBox));
                }

                Session::flash('status', ['type' => 'info', 'message' => __('Importing :current/:total Shipping Boxes', ['current' => ++$shippingBoxToImportIndex, 'total' => count($shippingBoxesToImport)])]);
                Session::save();
            }

            $this->batchWebhook($storedCollection, ShippingBox::class, ShippingBoxCollection::class, Webhook::OPERATION_TYPE_STORE);
            $this->batchWebhook($updatedCollection, ShippingBox::class, ShippingBoxCollection::class, Webhook::OPERATION_TYPE_UPDATE);
        }

        Session::flash('status', ['type' => 'success', 'message' => __('Shipping Boxes were successfully imported!')]);

        return __('Shipping Boxes were successfully imported!');
    }

    /**
     * @param array $data
     * @param ShippingBox|null $shippingBox
     * @param bool $update
     * @return StoreRequest|UpdateRequest
     */
    private function createRequestFromImport(array $data, ShippingBox $shippingBox = null, bool $update = false): UpdateRequest|StoreRequest
    {
        $requestData = [
            'customer_id' => $data[0]['customer_id'],
            'name' => $data[0]['name'],
            'height' => $data[0]['height'],
            'length' => $data[0]['length'],
            'width' => $data[0]['width'],
            'weight' => $data[0]['weight'],
            'cost' => $data[0]['cost'] ?? null,
        ];

        if (Arr::has($data, '0.barcode')) {
            $requestData['barcode'] = Arr::get($data, '0.barcode');
        }

        if ($update) {
            $requestDataID = [
                'id' => $shippingBox->id
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

        $shippingBoxCollection = $this->getQuery();

        if ($search) {
            $shippingBoxCollection = $this->searchQuery($search, $shippingBoxCollection);
        }

        $csvFileName = Str::kebab(auth()->user()->contactInformation->name) . '-shipping-box-export.csv';

        return app('csv')->export($request, $shippingBoxCollection->get(), ShippingBoxExportResource::columns(), $csvFileName, ShippingBoxExportResource::class);
    }
}
