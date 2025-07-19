<?php

namespace App\Traits;

use App\Components\BillingRates\Helpers\SlugComparerHelper;
use App\Models\ShippingCarrier;
use App\Models\ShippingMethod;

trait ShippingCalculatorTrait
{
    private function matchesByCarrier(array $settings, int $shipmentCarrierId): bool
    {
        $carriersAndMethods = $settings['carriers_and_methods'] ?? [];

        if (count($carriersAndMethods) === 0) {
            return false;
        }

        return collect($carriersAndMethods)->some(function ($configuredCarrierId) use ($shipmentCarrierId) {
            return SlugComparerHelper::compareByClass(ShippingCarrier::class, $configuredCarrierId, $shipmentCarrierId);
        });
    }

    private function matchesByShippingMethod(array $rateSetting, int $shippingMethodId): bool
    {
        $methodsSelected = $rateSetting['methods_selected'] ?? [];

        if (count($methodsSelected) === 0) {
            return true;
        }

        return collect($methodsSelected)
            ->some(function (array $selectedMethods) use ($shippingMethodId) {
                return collect($selectedMethods)
                    ->some(function (int $selectedMethodId) use ($shippingMethodId) {
                        return SlugComparerHelper::compareByClass(ShippingMethod::class, $selectedMethodId, $shippingMethodId);
                    });
            });
    }
}
