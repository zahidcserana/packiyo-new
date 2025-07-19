<?php

namespace App\Http\Controllers;

use App\Components\BillingRates\RequestValidator\BillingRequestValidator;
use App\Exceptions\BillingException;
use App\Http\Requests\BillingRate\AdHocStoreRequest;
use App\Http\Requests\BillingRate\AdHocUpdateRequest;
use App\Http\Requests\BillingRate\PackagingRateStoreRequest;
use App\Http\Requests\BillingRate\PackagingRateUpdateRequest;
use App\Http\Requests\BillingRate\PurchaseOrderStoreRequest;
use App\Http\Requests\BillingRate\PurchaseOrderUpdateRequest;
use App\Http\Requests\BillingRate\ShipmentsByPickingRateStoreRequest;
use App\Http\Requests\BillingRate\ShipmentsByPickingRateStoreRequestV2;
use App\Http\Requests\BillingRate\ShipmentsByPickingRateUpdateRequest;
use App\Http\Requests\BillingRate\ShipmentsByPickingRateUpdateRequestV2;
use App\Http\Requests\BillingRate\ShipmentsByShippingLabelStoreRequest;
use App\Http\Requests\BillingRate\ShipmentsByShippingLabelUpdateRequest;
use App\Http\Requests\BillingRate\ShippingRatesStoreRequest;
use App\Http\Requests\BillingRate\ShippingRatesUpdateRequest;
use App\Http\Requests\BillingRate\StorageByLocationStoreRequest;
use App\Http\Requests\BillingRate\StorageByLocationUpdateRequest;
use App\Http\Resources\BillingRateTableResource;
use App\Http\Resources\CarriersAndShippingMethodsTableResource;
use App\Http\Resources\CustomerAndShippingBoxTableResource;
use App\Models\BillingRate;
use App\Models\Customer;
use App\Models\RateCard;
use App\Models\ShippingCarrier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class BillingRateController extends Controller
{
    public function index()
    {
        return view('billing_rates.index');
    }

    public function dataTable(Request $request)
    {
        $billingRateCollection = BillingRate::query();
        $term = $request->input('search.value');

        if ($term) {
            $totalBillingRateCount = BillingRate::count();
            $billingRateCount = $billingRateCollection->count();
        } else {
            $totalBillingRateCount = $billingRateCollection->count();
            $billingRateCount = $totalBillingRateCount;
        }

        $billingRateCollection = $billingRateCollection->skip($request->get('start'))->limit($request->get('length'))->get();
        $billingRateResource = BillingRateTableResource::collection($billingRateCollection);

        return response()->json([
            'data' => $billingRateResource,
            'recordsTotal' => $totalBillingRateCount,
            'recordsFiltered' => $billingRateCount
        ]);
    }

    public function create(RateCard $rateCard, $type)
    {
        $rate = BillingRate::BILLING_RATE_TYPES[$type];
        $carriers = ShippingCarrier::whereIn('customer_id', app('user')->getSelectedCustomers()->pluck('id')->toArray())->get();

        return view('billing_rates.' . $rate['folder'] . '.create',
            [
                'rateCard' => $rateCard,
                'rateType' => $type,
                'rateTitle' => $rate['title'],
                'carriers' => $carriers
            ]
        );
    }

    public function edit(RateCard $rateCard, BillingRate $billingRate)
    {
        $folder = BillingRate::BILLING_RATE_TYPES[$billingRate['type']]['folder'];

        $settings = $billingRate['settings'];
        $carriers = ShippingCarrier::whereIn('customer_id', app('user')->getSelectedCustomers()->pluck('id')->toArray())->get();

        return view('billing_rates.' . $folder . '.edit', ['billingRate' => $billingRate, 'settings' => $settings, 'rateCard' => $rateCard, 'carriers' => $carriers, 'isReadonlyUser' => false]);
    }

    public function destroy(BillingRate $billingRate, RateCard $rateCard)
    {
        app('billingRate')->destroy($billingRate);

        return redirect()->back()->withStatus(__('Rate successfully deleted.'));
    }

    public function carriersAndMethods(Request $request)
    {
        $carriers = ShippingCarrier::whereIn('customer_id', app('user')->getSelectedCustomers()->pluck('id')->toArray());
        $term = $request->input('search.value');

        if ($term) {
            $totalCarrierCount = ShippingCarrier::count();
            $carrierCount = $carriers->count();
        } else {
            $totalCarrierCount = $carriers->count();
            $carrierCount = $totalCarrierCount;
        }

        $carriers = $carriers->skip($request->get('start'))->limit($request->get('length'))->get();
        $carrierCollection = CarriersAndShippingMethodsTableResource::collection($carriers);

        return response()->json([
            'data' => $carrierCollection,
            'recordsTotal' => $totalCarrierCount,
            'recordsFiltered' => $carrierCount
        ]);
    }

    public function customerAndShippingBox(Request $request): JsonResponse
    {
        $customers = app('user')->getCustomers();
        $term = $request->input('search.value');

        if ($term) {
            $totalCustomerCount = Customer::count();
            $customerCount = $customers->count();
        } else {
            $totalCustomerCount = $customers->count();
            $customerCount = $totalCustomerCount;
        }

        $customersWithBoxes = $customers->filter(function ($customer) {
            return $customer->shippingBoxes()->count() > 0;
        });

        $customerCollection = CustomerAndShippingBoxTableResource::collection($customersWithBoxes);

        return response()->json([
            'data' => $customerCollection,
            'recordsTotal' => $totalCustomerCount,
            'recordsFiltered' => $customerCount
        ]);
    }

    public function getCarrierMethods($billingRate, $shippingCarrier)
    {
        $selectedMethods = null;
        $billingRate = BillingRate::find($billingRate);
        $shippingCarrier = ShippingCarrier::find($shippingCarrier);

        if ($billingRate) {
            $settings = $billingRate['settings'];
            $selectedMethods = Arr::get($settings, 'methods', []);
        }

        $methodList = $shippingCarrier->shippingMethods;
        $carrierId = $shippingCarrier->id;

        return response(view('billing_rates.shipments_by_shipping_label.carrierMethodList',
            [
                'selectedMethods' => $selectedMethods,
                'methodList' => $methodList,
                'carrierId' => $carrierId
            ]));
    }

    public function getCustomerShippingBoxes(Customer $customer)
    {
        $methodList = $customer->shippingBoxes()->get();

        return response(
            view('billing_rates.packaging_rate.shippingBoxesList',
                [
                    'methodList' => $methodList,
                    'customerId' => $customer->id
                ])
        );
    }

    public function storageByLocationStore(StorageByLocationStoreRequest $request, RateCard $rateCard)
    {
        app('billingRate')->store($request->validated(), BillingRate::STORAGE_BY_LOCATION, $rateCard);

        return $this->rateResponse(true, $rateCard, BillingRate::STORAGE_BY_LOCATION);
    }

    public function storageByLocationUpdate(StorageByLocationUpdateRequest $request, RateCard $rateCard, BillingRate $billingRate)
    {
        app('billingRate')->update($request->validated(), $billingRate, $rateCard);

        return $this->rateResponse(false, $rateCard, BillingRate::STORAGE_BY_LOCATION);
    }

    public function shipmentsByShippingLabelStore(ShipmentsByShippingLabelStoreRequest $request, RateCard $rateCard)
    {
        $input = $request->validated();
        [$result, $errorMessage] = app('billingRate')->storeShippingRate($input, $rateCard);


        return !$result
            ? redirect()->route('billing_rates.create',
                ['rate_card' => $rateCard, 'type' => BillingRate::SHIPMENTS_BY_SHIPPING_LABEL]
            )->withErrors(__($errorMessage))->withInput($input)
            : $this->rateResponse(true, $rateCard, BillingRate::SHIPMENTS_BY_SHIPPING_LABEL);
    }

    public function shipmentsByShippingLabelUpdate(ShipmentsByShippingLabelUpdateRequest $request, RateCard $rateCard, BillingRate $billingRate)
    {
        $input = $request->validated();
        [$result, $errorMessage] = app('billingRate')->updateShippingRate($input, $billingRate, $rateCard);

        return !$result
            ? redirect()->route('billing_rates.edit', ['rate_card' => $rateCard, 'billing_rate' => $billingRate])
                ->withErrors(__($errorMessage))->withInput($input)
            : $this->rateResponse(false, $rateCard, BillingRate::SHIPMENTS_BY_SHIPPING_LABEL);
    }

    public function packagingRateStore(PackagingRateStoreRequest $request, RateCard $rateCard)
    {
        $input = $request->validated();
        [$result, $errorMessage] = app('billingRate')->storePackagingRate($input, $rateCard);

        return !$result
            ? redirect()->route('billing_rates.create',
                ['rate_card' => $rateCard, 'type' => BillingRate::PACKAGING_RATE]
            )->withErrors(__($errorMessage))->withInput($input)
            : $this->rateResponse(true, $rateCard, BillingRate::PACKAGING_RATE);
    }

    public function packagingRateUpdate(PackagingRateUpdateRequest $request, RateCard $rateCard, BillingRate $billingRate)
    {
        $input = $request->validated();
        [$result, $errorMessage] = app('billingRate')->updatePackagingRate($input, $billingRate, $rateCard);

        return !$result
            ? redirect()->route('billing_rates.edit', ['rate_card' => $rateCard, 'billing_rate' => $billingRate])
                ->withErrors(__($errorMessage))->withInput($input)
            : $this->rateResponse(false, $rateCard, BillingRate::PACKAGING_RATE);

    }

    public function shipmentsByPickingRateStoreV2(ShipmentsByPickingRateStoreRequestV2 $request, RateCard $rateCard)
    {
        app('billingRate')->store($request->validated(), BillingRate::SHIPMENTS_BY_PICKING_RATE_V2, $rateCard);

        return $this->rateResponse(true, $rateCard, BillingRate::SHIPMENTS_BY_PICKING_RATE_V2);
    }

    public function shipmentsByPickingRateUpdateV2(ShipmentsByPickingRateUpdateRequestV2 $request, RateCard $rateCard, BillingRate $billingRate)
    {
        app('billingRate')->update($request->validated(), $billingRate, $rateCard);

        return $this->rateResponse(false, $rateCard, BillingRate::SHIPMENTS_BY_PICKING_RATE_V2);
    }

    public function adHocStore(AdHocStoreRequest $request, RateCard $rateCard)
    {
        app('billingRate')->store($request->validated(), BillingRate::AD_HOC, $rateCard);

        return $this->rateResponse(true, $rateCard, BillingRate::AD_HOC);
    }

    public function adHocUpdate(AdHocUpdateRequest $request, RateCard $rateCard, BillingRate $billingRate)
    {
        app('billingRate')->update($request->validated(), $billingRate, $rateCard);

        return $this->rateResponse(false, $rateCard, BillingRate::AD_HOC);
    }

    public function purchaseOrderStore(PurchaseOrderStoreRequest $request, RateCard $rateCard)
    {
        try {
            app('billingRate')->storePurchaseOrderRate($request->validated(), $rateCard);

            return $this->rateResponse(true, $rateCard, BillingRate::PURCHASE_ORDER);
        } catch (BillingException $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function purchaseOrderUpdate(PurchaseOrderUpdateRequest $request, RateCard $rateCard, BillingRate $billingRate)
    {
        app('billingRate')->updatePurchaseOrderRate($request->validated(), $billingRate, $rateCard);

        return $this->rateResponse(false, $rateCard, BillingRate::PURCHASE_ORDER);
    }

    public function rateResponse($redirect, $rateCard, $type = null): RedirectResponse
    {
        $message = __('Rate successfully updated.');

        if ($redirect) {
            $message = __('Rate successfully created');
        }

        return redirect()->route('rate_cards.edit', ['rate_card' => $rateCard->id, '#' . $type])->withStatus(__($message));
    }
}
