<?php

namespace App\Http\Controllers;

use App\Features\LockOrderItemsWhilePicking;
use App\Features\AllowCancelledItemsOnBulkShip;
use App\Features\AllowGenericOnBulkShipping;
use App\Features\AllowNonSellableAllocation;
use App\Features\AllowOverlappingRates;
use App\Features\DataWarehousing;
use App\Features\DisableQuantityForKitInSlips;
use App\Features\FirstPickFeeFix;
use App\Features\LoginLogo;
use App\Features\LotTrackingConstraints;
use App\Features\NewCustomsPrice;
use App\Features\VisibleOrderChannelPayload;
use App\Features\RequiredReadyToPickForPacking;
use App\Features\OrderSearchByNameEmail;
use App\Features\PartialOrdersBulkShip;
use App\Features\PendingOrderSlip;
use App\Features\PreventDuplicateBarcodes;
use App\Features\ReservePickingQuantities;
use App\Features\SelfService;
use App\Features\MultiWarehouse;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;

class FeatureController extends Controller
{
    public function show()
    {
        $data = [
            'instanceFeatures' => [
                LockOrderItemsWhilePicking::class,
                VisibleOrderChannelPayload::class,
                RequiredReadyToPickForPacking::class,
                MultiWarehouse::class,
                ReservePickingQuantities::class,
                AllowGenericOnBulkShipping::class,
                AllowNonSellableAllocation::class,
                PendingOrderSlip::class,
                DataWarehousing::class,
                PreventDuplicateBarcodes::class,
                LotTrackingConstraints::class,
                OrderSearchByNameEmail::class,
                SelfService::class,
                AllowCancelledItemsOnBulkShip::class,
                DisableQuantityForKitInSlips::class,
                AllowOverlappingRates::class,
                PartialOrdersBulkShip::class
            ],
            'customerFeatures' => [
                NewCustomsPrice::class,
            ]
        ];

        // Get all customers that are standalone or 3PL.
        $selectedCustomers = app('user')->getSelectedCustomers()->filter(fn (Customer $customer) => !$customer->is3plChild());
        $data['selectedCustomers'] = $selectedCustomers;

        if ($selectedCustomers->count() == 1 && $selectedCustomers->first()->is3pl()) {
            $data['customerFeatures'][] = FirstPickFeeFix::class;
        }

        return view('features.show', $data);
    }

    public function update(Request $request)
    {
        $request->validate([
            LoginLogo::class => 'image|max:32768',
        ]);

        foreach ($request->get('features', []) as $feature => $enabled) {
            $featureInteraction = Feature::for('instance');

            if ($enabled) {
                $featureInteraction->activate($feature);
            } else {
                $featureInteraction->deactivate($feature);
            }
        }

        $customerId = $request->get('customer_id');

        if ($customerId) {
            $customer = Customer::whereId($customerId)->first();

            foreach ($request->get('customerFeatures', []) as $feature => $enabled) {
                $featureInteraction = Feature::for($customer);

                if ($enabled) {
                    $featureInteraction->activate($feature);
                } else {
                    $featureInteraction->deactivate($feature);
                }
            }
        }

        $this->handleLoginLogoImage($request);

        return redirect()->route('features.show')->withStatus(__('Features were saved.'));
    }

    protected function handleLoginLogoImage(Request $request)
    {
        $featureInteraction = Feature::for('instance');
        $currentImagePath = $featureInteraction->value(LoginLogo::class);

        if ($request->hasFile(LoginLogo::class)) {
            if (!is_null($currentImagePath)) {
                $currentStoragePath = str_replace(Storage::url(''), 'public/', $currentImagePath);
                Storage::delete($currentStoragePath);
            }
            $path = Storage::url($request->file(LoginLogo::class)->store('public/images'));
            $featureInteraction->activate(LoginLogo::class, $path);
        } elseif (!is_null($currentImagePath) && $request->has('delete_login_logo')) {
            $currentStoragePath = str_replace(Storage::url(''), 'public/', $currentImagePath);
            Storage::delete($currentStoragePath);
            $featureInteraction->activate(LoginLogo::class, null);
        }
    }
}
