<?php

namespace App\Components;

use App\Http\Requests\RateCard\StoreRequest;
use App\Http\Requests\RateCard\StoreBatchRequest;
use App\Http\Requests\RateCard\UpdateRequest;
use App\Http\Requests\RateCard\UpdateBatchRequest;
use App\Http\Requests\RateCard\DestroyRequest;
use App\Http\Requests\RateCard\DestroyBatchRequest;
use App\Models\RateCard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RateCardComponent extends BaseComponent
{
    public function store(StoreRequest $request)
    {
        $input = $request->validated();

        return RateCard::create($input);
    }

    public function storeBatch(StoreBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $storeRequest = StoreRequest::make($record);
            $responseCollection->add($this->store($storeRequest, false));
        }

        return $responseCollection;
    }

    public function update(UpdateRequest $request, RateCard $rateCard)
    {
        $input = $request->validated();

        $rateCard->update($input);

        return $rateCard;
    }

    public function updateBatch(UpdateBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $updateRequest = UpdateRequest::make($record);
            $rateCard = RateCard::find($record['id']);

            $responseCollection->add($this->update($updateRequest, $rateCard, false));
        }

        return $responseCollection;
    }

    public function destroy(DestroyRequest $request = null, RateCard $rateCard = null)
    {
        try {
            $rateCard->delete();

        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function destroyBatch(DestroyBatchRequest $request)
    {
        $responseCollection = new Collection();
        $input = $request->validated();

        foreach ($input as $record) {
            $destroyRequest = DestroyRequest::make($record);
            $rateCard = RateCard::where('id', $record['id'])->first();

            $responseCollection->add($this->destroy($destroyRequest, $rateCard));
        }

        return $responseCollection;
    }
}
