<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\AutomationActions\SetShippingMethodAction;
use App\Models\Automations\AppliesToCustomers;
use App\Models\ShippingCarrier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

trait AddsSetShippingMethodAction
{
    protected function addSetShippingMethodAction(AutomationChoices $automationChoices): SetShippingMethodAction
    {
        $carriers = $this->getCarriers($automationChoices);

        $shippingCarrierName = $this->choice(
            __('What is the intended carrier?'),
            $carriers->mapWithKeys((function (ShippingCarrier $carrier) {
                $belongs_to_client = $carrier->customer->parent_id;

                $carrierName = $carrier->name;

                if ($belongs_to_client) {
                    $carrierName .= ' (' . $carrier->customer->contactInformation->name . ')';
                }

                return [$carrier->id => $carrierName];
            }))->toArray()
        );

        // If we chose a carrier that has (), which means it belongs to a client, we must find it in the carriers array
        $belongsToClient = Str::contains($shippingCarrierName, '(');

        if ($belongsToClient) {
            $selectedShippingCarrier = $carriers->filter(function (ShippingCarrier $carrier) use ($shippingCarrierName) {
                return Str::contains($shippingCarrierName, $carrier->customer->contactInformation->name) && $carrier->customer->parent_id;
            })->firstOrFail();
        } else {
            $selectedShippingCarrier = $carriers->filter(function (ShippingCarrier $carrier) use ($shippingCarrierName) {
                return $carrier->name === $shippingCarrierName;
            })->firstOrFail();
        }

        $shippingMethodName = $this->choice(
            __('What is the intended shipping method?'),

            $selectedShippingCarrier->shippingMethods->pluck('name')->toArray()
        );
        $shippingMethod = $selectedShippingCarrier->shippingMethods()
            ->where('name', $shippingMethodName)
            ->firstOrFail();

        $force = $this->choice(
            __('Should the shipping method be set when the triggering event was itself an update to the shipping method?'),
            [__('yes'), __('no')], // Choices.
            __('no') // Default.
        );

        $action = new SetShippingMethodAction([
            'force' => $force
        ]);
        $action->shippingMethod()->associate($shippingMethod);

        return $action;
    }

    protected function getCarriers(AutomationChoices $automationChoices): Collection
    {
        $ownerCustomer = $automationChoices->getOwnerCustomer();
        $chosenClients = $automationChoices->getTargetCustomers();

        return ShippingCarrier::query()
            ->when($automationChoices->getAppliesTo() === AppliesToCustomers::SOME && $chosenClients?->count() === 1,
                fn (Builder $query) => $query->whereIn('customer_id', [$ownerCustomer->id, $chosenClients->first()->id]),
                fn (Builder $query) => $query->where('customer_id', $ownerCustomer->id)
            )
            ->with('customer.contactInformation')
            ->get(['id', 'name', 'customer_id']);
    }
}
