<?php

namespace App\Components;

use App\Http\Requests\Lot\{DestroyBatchRequest,
    DestroyRequest,
    StoreBatchRequest,
    StoreRequest,
    UpdateBatchRequest,
    UpdateRequest
};
use Illuminate\Http\Request;
use App\Http\Resources\{LotCollection, LotResource};
use App\Models\{Lot, Product, Webhook};
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

class LotComponent extends BaseComponent
{
    public function store(StoreRequest $request, $fireWebhook = true)
    {
        $input = $request->validated();

        $input['customer_id'] = Product::find($request->get('product_id'))->customer_id;

        $lot = Lot::create($input);

        if ($fireWebhook) {
            $this->webhook(
                new LotResource($lot),
                Lot::class,
                Webhook::OPERATION_TYPE_STORE,
                $lot->customer_id
            );
        }

        return $lot;
    }

    public function storeBatch(StoreBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $storeRequest = StoreRequest::make($record);
            $responseCollection->add($this->store($storeRequest, false));
        }

        $this->batchWebhook($responseCollection, Lot::class, LotCollection::class, Webhook::OPERATION_TYPE_STORE);

        return $responseCollection;
    }

    public function update(UpdateRequest $request, Lot $lot, $fireWebhook = true)
    {
        $input = $request->validated();

        $lot->update($input);

        if ($fireWebhook) {
            $this->webhook(new LotResource($lot), Lot::class, Webhook::OPERATION_TYPE_UPDATE, $lot->customer_id);
        }

        return $lot;
    }

    public function updateBatch(UpdateBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $updateRequest = UpdateRequest::make($record);
            $lot = Lot::find($record['id']);
            $responseCollection->add($this->update($updateRequest, $lot, false));
        }

        $this->batchWebhook($responseCollection, Lot::class, LotCollection::class, Webhook::OPERATION_TYPE_UPDATE);

        return $responseCollection;
    }

    public function destroy(DestroyRequest $request, Lot $lot, $fireWebhook = true)
    {
        $lot->delete();

        $response = ['id' => $lot->id, 'customer_id' => $lot->customer_id];

        if ($fireWebhook == true) {
            $this->webhook($response, Lot::class, Webhook::OPERATION_TYPE_DESTROY, $lot->customer_id);
        }

        return $response;
    }

    public function destroyBatch(DestroyBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $destroyRequest = DestroyRequest::make($record);
            $lot = Lot::find($record['id']);

            $responseCollection->add($this->destroy($destroyRequest, $lot, false));
        }

        $this->batchWebhook($responseCollection, Lot::class, ResourceCollection::class, Webhook::OPERATION_TYPE_DESTROY);

        return $responseCollection;
    }

    public function filterLots(Request $request)
    {
        $lots = null;
        $term = $request->get('term');
        $productId = $request->get('product_id');

        $customers = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        if ($term) {
            $term = $term . '%';
            $lots = Lot::with('supplier.contactInformation')
                ->whereIn('customer_id', $customers)
                ->where('name', 'like', $term)
                ->where('product_id', $productId)
                ->get();
        }

        return $lots;
    }
}
