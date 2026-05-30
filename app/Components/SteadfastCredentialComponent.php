<?php

namespace App\Components;

use App\Http\Requests\SteadfastCredential\{
    DestroyBatchRequest,
    DestroyRequest,
    StoreBatchRequest,
    StoreRequest,
    UpdateBatchRequest,
    UpdateRequest
};
use App\Models\{Customer, SteadfastCredential};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SteadfastCredentialComponent extends BaseComponent
{
    public function store(StoreRequest $request, Customer $customer = null): Model|SteadfastCredential
    {
        $input = $request->validated();

        if (!is_null($customer)) {
            $input['customer_id'] = $customer->id;
        }

        return SteadfastCredential::create($input);
    }

    public function storeBatch(StoreBatchRequest $request): Collection
    {
        $responseCollection = new Collection();

        foreach ($request->validated() as $record) {
            $responseCollection->add($this->store(StoreRequest::make($record)));
        }

        return $responseCollection;
    }

    public function update(UpdateRequest $request, SteadfastCredential $steadfastCredential): SteadfastCredential
    {
        $steadfastCredential->update($request->validated());

        return $steadfastCredential;
    }

    public function updateBatch(UpdateBatchRequest $request): Collection
    {
        $responseCollection = new Collection();

        foreach ($request->validated() as $record) {
            $credential = SteadfastCredential::find($record['id']);
            $responseCollection->add($this->update(UpdateRequest::make($record), $credential));
        }

        return $responseCollection;
    }

    public function destroy(DestroyRequest $request, SteadfastCredential $steadfastCredential): array
    {
        $steadfastCredential->delete();

        return ['id' => $steadfastCredential->id, 'customer_id' => $steadfastCredential->customer_id];
    }

    public function destroyBatch(DestroyBatchRequest $request): Collection
    {
        $responseCollection = new Collection();

        foreach ($request->validated() as $record) {
            $credential = SteadfastCredential::find($record['id']);
            $responseCollection->add($this->destroy(DestroyRequest::make($record), $credential));
        }

        return $responseCollection;
    }
}
