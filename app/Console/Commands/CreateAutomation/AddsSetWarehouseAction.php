<?php

namespace App\Console\Commands\CreateAutomation;

use App\Models\AutomationActions\SetWarehouseAction;
use App\Models\Warehouse;

trait AddsSetWarehouseAction
{
    use IncludesSingleTargetCustomer;

    protected function addSetWarehouseAction(AutomationChoices $automationChoices): SetWarehouseAction
    {
        $customers = $this->getCustomersForFilter($automationChoices);
        $customerIds = $customers->pluck('id')->toArray();

        $warehouseId = $this->choice(
            __('Which warehouse should the orders be assigned to?'),
            Warehouse::whereIn('customer_id', $customerIds)
                ->get()
                ->mapWithKeys(fn (Warehouse $warehouse) => [__('ID :id', ['id' => $warehouse->id])
                    => __(':name - :address :city :state :zip :country', [
                        'name' => $warehouse->name,
                        'address' => $warehouse->contactInformation->address,
                        'city' => $warehouse->contactInformation->city,
                        'state' => $warehouse->contactInformation->state,
                        'zip' => $warehouse->contactInformation->zip,
                        'country' => $warehouse->contactInformation->country->iso_3166_2 ?? '',
                    ])
                ])
                ->toArray()
        );
        $warehouse = Warehouse::findOrFail((int) substr($warehouseId, 3));

        $action = new SetWarehouseAction();
        $action->warehouse()->associate($warehouse);

        return $action;
    }
}
