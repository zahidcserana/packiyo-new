<?php

namespace App\Components;

use App\Http\Requests\PathaoCredential\{DestroyBatchRequest,
    DestroyRequest,
    StoreBatchRequest,
    StoreRequest,
    UpdateBatchRequest,
    UpdateRequest};
use App\Models\{Customer, PathaoCredential};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PathaoCredentialComponent extends BaseComponent
{
    /**
     * @param StoreRequest $request
     * @param Customer|null $customer
     * @return PathaoCredential|Model
     */
    public function store(StoreRequest $request, Customer $customer = null): Model|PathaoCredential
    {
        $input = $request->validated();

        if (!is_null($customer)) {
            $input['customer_id'] = $customer->id;
        }

        return PathaoCredential::create($input);
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
     * @param PathaoCredential $pathaoCredential
     * @return PathaoCredential
     */
    public function update(UpdateRequest $request, PathaoCredential $pathaoCredential): PathaoCredential
    {
        $input = $request->validated();

        $pathaoCredential->update($input);

        return $pathaoCredential;
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
            $pathaoCredential = PathaoCredential::find($record['id']);

            $responseCollection->add($this->update($updateRequest, $pathaoCredential));
        }

        return $responseCollection;
    }

    /**
     * @param DestroyRequest $request
     * @param PathaoCredential $pathaoCredential
     * @return array
     */
    public function destroy(DestroyRequest $request, PathaoCredential $pathaoCredential): array
    {
        $pathaoCredential->delete();

        return ['id' => $pathaoCredential->id, 'customer_id' => $pathaoCredential->customer_id];
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
            $pathaoCredential = PathaoCredential::find($record['id']);

            $responseCollection->add($this->destroy($destroyRequest, $pathaoCredential));
        }

        return $responseCollection;
    }
}
