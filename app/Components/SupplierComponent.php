<?php

namespace App\Components;

use App\Http\Requests\Supplier\DestroyBatchRequest;
use App\Http\Requests\Supplier\DestroyRequest;
use App\Http\Requests\Supplier\StoreBatchRequest;
use App\Http\Requests\Supplier\StoreRequest;
use App\Http\Requests\Supplier\UpdateBatchRequest;
use App\Http\Requests\Supplier\UpdateRequest;
use App\Http\Requests\Csv\{ImportCsvRequest, ExportCsvRequest};
use App\Http\Resources\{ExportResources\SupplierExportResource, SupplierCollection, SupplierResource};
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\{Arr, Str, Collection, Facades\Session};
use Webpatser\Countries\Countries;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierComponent extends BaseComponent
{
    public function store(StoreRequest $request, $fireWebhook = true)
    {
        $input = $request->validated();

        $contactInformationData = Arr::get($input, 'contact_information');

        Arr::forget($input, 'contact_information');

        $supplier = Supplier::create($input);

        $this->createContactInformation($contactInformationData, $supplier);

        if ($fireWebhook) {
            $this->webhook(new SupplierResource($supplier), Supplier::class, Webhook::OPERATION_TYPE_STORE, $supplier->customer_id);
        }

        return $supplier;
    }

    public function storeBatch(StoreBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $storeRequest = StoreRequest::make($record);
            $responseCollection->add($this->store($storeRequest, false));
        }

        $this->batchWebhook($responseCollection, Supplier::class, SupplierCollection::class, Webhook::OPERATION_TYPE_STORE);

        return $responseCollection;
    }

    public function update(UpdateRequest $request, Supplier $supplier, $fireWebhook = true)
    {
        $input = $request->validated();

        $supplier->contactInformation->update(Arr::get($input, 'contact_information'));

        if (array_key_exists('product_id', $input)) {
            foreach ($input['product_id'] as $productId) {
                $supplier->products()->attach($productId);
            }
        }

        Arr::forget($input, 'contact_information');
        $supplier->update($input);

        if ($fireWebhook) {
            $this->webhook(new SupplierResource($supplier), Supplier::class, Webhook::OPERATION_TYPE_UPDATE, $supplier->customer_id);
        }

        return $supplier;
    }

    public function updateBatch(UpdateBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $updateRequest = UpdateRequest::make($record);
            $supplier = Supplier::find($record['id']);

            $responseCollection->add($this->update($updateRequest, $supplier, false));
        }

        $this->batchWebhook($responseCollection, Supplier::class, SupplierCollection::class, Webhook::OPERATION_TYPE_UPDATE);

        return $responseCollection;
    }

    public function destroy(DestroyRequest $request, Supplier $supplier, $fireWebhook = true)
    {
        $supplier->delete();

        $response = ['id' => $supplier->id, 'customer_id' => $supplier->customer_id];

        if ($fireWebhook) {
            $this->webhook($response, Supplier::class, Webhook::OPERATION_TYPE_DESTROY, $supplier->customer_id);
        }

        return $response;
    }

    public function destroyBatch(DestroyBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $destroyRequest = DestroyRequest::make($record);
            $supplier = Supplier::find($record['id']);

            $responseCollection->add($this->destroy($destroyRequest, $supplier, false));
        }

        $this->batchWebhook($responseCollection, Supplier::class, ResourceCollection::class, Webhook::OPERATION_TYPE_DESTROY);

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

    public function filterProducts(Request $request, Customer $customer): JsonResponse
    {
        $term = $request->get('term');
        $results = [];

        if ($term) {
            $term = $term . '%';

            $products = Product::where('customer_id', $customer->id)
                ->where(static function ($query) use ($term) {
                    return $query->where('sku', 'like', $term)
                        ->orWhere('name', 'like', $term);
                })
                ->get();

            foreach ($products as $product) {
                $results[] = [
                    'id' => $product->id,
                    'text' => 'SKU: ' . $product->sku . ', NAME:' . $product->name,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'quantity' => $product->quantity_available ?? 0
                ];
            }
        }

        return response()->json([
            'results' => $results
        ]);
    }

    public function filterByProduct(Request $request, Product $product)
    {
        $term = $request->get('term');
        $suppliers = null;

        if ($term) {
            $term = $term . '%';
            $suppliers = Supplier::whereHas('products', static function ($query) use($product) {
                $query->where('products.id', $product->id);
            })
            ->whereHas('contactInformation', function ($query) use ($term) {

                $query->where('name', 'like', $term)
                    ->orWhere('company_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('zip', 'like', $term)
                    ->orWhere('city', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            })
            ->get();
        }

        return $suppliers;
    }

    /**
     * @param ExportCsvRequest $request
     * @return StreamedResponse
     */
    public function exportCsv(ExportCsvRequest $request): StreamedResponse
    {
        $input = $request->validated();
        $search = $input['search']['value'];

        $vendors = $this->getQuery();

        if ($search) {
            $vendors = $this->searchQuery($search, $vendors);
        }

        $csvFileName = Str::kebab(auth()->user()->contactInformation->name) . '-vendors-export.csv';

        return app('csv')->export($request, $vendors->get(), SupplierExportResource::columns(), $csvFileName, SupplierExportResource::class);
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
            app('csv')->unsetCsvHeader($importLines, 'vendor_contact_information_name'),
            SupplierExportResource::columns()
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

                $supplier = Supplier::whereHas('contactInformation', static function($query) use ($data) {
                        $query->where('name', $data['vendor_contact_information_name']);
                    })
                    ->where('customer_id', $data['customer_id'])->first();

                if ($supplier) {
                    $updatedCollection->add($this->update($this->createRequestFromImport($data, $supplier, true), $supplier, false));
                } else {
                    $storedCollection->add($this->store($this->createRequestFromImport($data), false));
                }

                Session::flash('status', ['type' => 'info', 'message' => __('Importing :current/:total vendors', ['current' => $importLineIndex + 1, 'total' => count($importLines)])]);
                Session::save();
            }

            $this->batchWebhook($storedCollection, Supplier::class, SupplierCollection::class, Webhook::OPERATION_TYPE_STORE);
            $this->batchWebhook($updatedCollection, Supplier::class, SupplierCollection::class, Webhook::OPERATION_TYPE_UPDATE);
        }

        Session::flash('status', ['type' => 'success', 'message' => __('Suppliers were successfully imported!')]);

        return __('Suppliers were successfully imported!');
    }

      /**
     * @param array $data
     * @param Supplier|null $supplier
     * @param bool $update
     * @return StoreRequest|UpdateRequest
     */
    private function createRequestFromImport(array $data, Supplier $supplier = null, bool $update = false)
    {
        $countryId = null;

        if (Arr::has($data, 'vendor_contact_information_country')) {
            if ($country = Countries::where('iso_3166_2', Arr::get($data, 'vendor_contact_information_country'))->first()) {
                $countryId = $country->id;
            }
        }

        $requestData = [
            'customer_id' => Arr::get($data, 'customer_id'),
            'currency' => Arr::get($data, 'currency'),
            'internal_note' => Arr::get($data, 'internal_note'),
            'default_purchase_order_note' => Arr::get($data, 'default_purchase_order_note'),
            'contact_information' => [
                'name' => Arr::get($data, 'vendor_contact_information_name'),
                'company_name' => Arr::get($data, 'vendor_contact_information_company_name'),
                'company_number' => Arr::get($data, 'vendor_contact_information_company_number'),
                'address' => Arr::get($data, 'vendor_contact_information_address'),
                'address2' => Arr::get($data, 'vendor_contact_information_address2'),
                'zip' => Arr::get($data, 'vendor_contact_information_zip'),
                'city' => Arr::get($data, 'vendor_contact_information_city'),
                'state' => Arr::get($data, 'vendor_contact_information_state'),
                'country_id' => $countryId,
                'phone' => Arr::get($data, 'vendor_contact_information_phone'),
                'email' => Arr::get($data, 'vendor_contact_information_email')
            ]
        ];

        if ($update) {
            $requestData['id'] = $supplier->id;
        }

        return $update ? UpdateRequest::make($requestData) : StoreRequest::make($requestData);
    }

    /**
     * @param string $sortColumnName
     * @param string $sortDirection
     * @return mixed
     */
    public function getQuery(string $sortColumnName = 'suppliers.id', string $sortDirection = 'desc')
    {
        $customers = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        $supplierCollection = Supplier::join ('customers', 'suppliers.customer_id', '=', 'customers.id')
            ->join('contact_informations AS customer_contact_information', 'customers.id', '=', 'customer_contact_information.object_id')
            ->join('contact_informations', 'suppliers.id', '=', 'contact_informations.object_id')
            ->where('customer_contact_information.object_type', Customer::class)
            ->where('contact_informations.object_type', Supplier::class)
            ->whereIn('suppliers.customer_id', $customers)
            ->select('suppliers.*')
            ->groupBy('suppliers.id')
            ->orderBy($sortColumnName, $sortDirection);

        return $supplierCollection;
    }

    /**
     * @param string $term
     * @param $vendorCollection
     * @return mixed
     */
    public function searchQuery(string $term, $vendorCollection): mixed
    {
        $term = $term . '%';

        return $vendorCollection
                ->whereHas('contactInformation', function($query) use ($term) {
                    $query->where('name', 'like', $term)
                        ->orWhere('address', 'like', $term)
                        ->orWhere('city', 'like', $term)
                        ->orWhere('zip', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
    }
}
