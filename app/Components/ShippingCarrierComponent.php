<?php

namespace App\Components;

use App\Http\Requests\ShippingCarrier\DestroyRequest;
use App\Http\Requests\ShippingCarrier\StoreRequest;
use App\Http\Requests\ShippingCarrier\UpdateRequest;
use App\Http\Requests\ShippingCarrier\DisconnectionRequest;
use App\Models\ShippingCarrier;

class ShippingCarrierComponent extends BaseComponent
{
    public function store(StoreRequest $request, $fireWebhook = true)
    {
        $input = $request->validated();

        return app('tribirdShipping')->setupCarrierConnection($input);
    }

    public function update(UpdateRequest $request, ShippingCarrier $shippingCarrier, $fireWebhook = true)
    {
        $input = $request->validated();

        $response = app('tribirdShipping')->updateCarrierConnection($shippingCarrier, $input['configurations']);

        if ($response && $response['data']) {
            $shippingCarrier->carrier_account = $response['data']['carrier_account'];
            $shippingCarrier->save();

            $shippingCarrier->credential->save();
        }

        return $response;
    }

    public function destroy(DestroyRequest $request, ShippingCarrier $shippingCarrier, $fireWebhook = true)
    {
        $response = app('tribirdShipping')->deleteCarrierConnection($shippingCarrier);

        if (is_null($response) || $response['errors']) {
            return false;
        }

        foreach ($shippingCarrier->shippingMethods as $shippingMethod) {
            $shippingMethod->shippingMethodMappings()->forceDelete();

            $shippingMethod->returnShippingMethodMappings()->forceDelete();
        }

        $shippingCarrier->shippingMethods()->delete();
        $shippingCarrier->delete();

        return true;
    }

    public function disconnectCarrier(DisconnectionRequest $request, ShippingCarrier $shippingCarrier)
    {
        $input = $request->validated();

        if (strcasecmp($input['disconnection_text'], 'Disconnect') != 0 ) {
            return false;
        }

        $shippingCarrier->active = false;
        $shippingCarrier->save();

        return true;
    }

    public function connectCarrier(ShippingCarrier $shippingCarrier)
    {
        $shippingCarrier->active = true;
        $shippingCarrier->save();

        return true;
    }

    /**
     * @param $filterInputs
     * @param string $tableColumnName
     * @param string $sortDirection
     * @return mixed
     */
    public function getQuery($filterInputs, string $tableColumnName = 'shipping_carriers.name', string $sortDirection = 'desc')
    {
        return ShippingCarrier::where(function ($query) use ($filterInputs) {
            if (isset($filterInputs['name'])) {
                $query->where('name', $filterInputs['name']);
            }
        })
        ->orderBy($tableColumnName, $sortDirection);
    }
}
