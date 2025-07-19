<?php

namespace App\Components;

use App\Http\Requests\EasypostCredential\{DestroyBatchRequest,
    DestroyRequest,
    StoreBatchRequest,
    StoreRequest,
    UpdateBatchRequest,
    UpdateRequest};
use App\Models\{Customer, EasypostCredential};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;

class EasypostCredentialComponent extends BaseComponent
{
    /**
     * @param StoreRequest $request
     * @param Customer|null $customer
     * @return EasypostCredential|Model
     */
    public function store(StoreRequest $request, Customer $customer = null)
    {
        $input = $request->validated();

        if (!is_null($customer)) {
            $input['customer_id'] = $customer->id;

            $input['reference_prefix'] = $this->getReferencePrefix($customer, $request);
        }

        return EasypostCredential::create($input);
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

        return $responseCollection;
    }

    /**
     * @param UpdateRequest $request
     * @param EasypostCredential $easypostCredential
     * @return EasypostCredential
     */
    public function update(UpdateRequest $request, EasypostCredential $easypostCredential): EasypostCredential
    {
        $input = $request->validated();

        if (empty($input['reference_prefix'])) {
            $input['reference_prefix'] = $this->getReferencePrefix($easypostCredential->customer, $request);
        }

        $easypostCredential->update($input);

        return $easypostCredential;
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
            $easypostCredential = EasypostCredential::find($record['id']);

            $responseCollection->add($this->update($updateRequest, $easypostCredential));
        }

        return $responseCollection;
    }

    /**
     * @param DestroyRequest $request
     * @param EasypostCredential $easypostCredential
     * @return array
     */
    public function destroy(DestroyRequest $request, EasypostCredential $easypostCredential): array
    {
        $easypostCredential->delete();

        return ['id' => $easypostCredential->id, 'customer_id' => $easypostCredential->customer_id];
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
            $easypostCredential = EasypostCredential::find($record['id']);

            $responseCollection->add($this->destroy($destroyRequest, $easypostCredential));
        }

        return $responseCollection;
    }

    /**
     * @param EasypostCredential $easypostCredential
     * @return bool
     */
    public function batchShipments(EasypostCredential $easypostCredential): bool
    {
        $cacheKey = 'easypost_batch_shipments_' . $easypostCredential->id;

        if (Cache::has($cacheKey)) {
            return false;
        }

        Cache::set($cacheKey, 1, 60);

        foreach ($easypostCredential->shippingCarriers as $shippingCarrier) {
            app('easypostShipping')->manifest($shippingCarrier);
        }

        Cache::delete($cacheKey);

        return true;
    }

    /**
     * @param EasypostCredential $easypostCredential
     * @return bool
     * @throws InvalidArgumentException
     */
    public function scanformBatches(EasypostCredential $easypostCredential): bool
    {
        $cacheKey = 'easypost_scanform_batches_' . $easypostCredential->id;

        if (Cache::has($cacheKey)) {
            return false;
        }

        Cache::set($cacheKey, 1, 60);

        foreach ($easypostCredential->shippingCarriers as $shippingCarrier) {
            app('easypostShipping')->scanformBatches($shippingCarrier);
        }

        Cache::delete($cacheKey);

        return true;
    }

    /**
     * @param Customer $customer
     * @param Request $request
     * @return string
     */
    private function getReferencePrefix(Customer $customer, Request $request): string
    {
        return Str::slug($request->getHttpHost() . ' ' . $customer->contactInformation->name);
    }
}
