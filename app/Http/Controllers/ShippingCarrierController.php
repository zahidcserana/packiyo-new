<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShippingCarrier\DestroyRequest;
use App\Http\Requests\ShippingCarrier\StoreRequest;
use App\Http\Requests\ShippingCarrier\UpdateRequest;
use App\Http\Requests\ShippingCarrier\DisconnectionRequest;
use App\Http\Resources\ShippingCarrierTableResource;
use App\Models\ShippingCarrier;
use Illuminate\Http\{JsonResponse, Request, Response};

class ShippingCarrierController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ShippingCarrier::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $customers = app()->user->getSelectedCustomers()->pluck('id')->toArray();

        return view('shipping_carrier.index', [
            'page' => 'shipping_carriers',
            'datatableOrder' => app()->editColumn->getDatatableOrder('shipping-carrier'),
        ]);
    }

    public function dataTable(Request $request): \Illuminate\Http\JsonResponse
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $filterInputs =  $request->get('filter_form');

        $tableColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
        $sortDirection = $columnOrder[0]['dir'];
        $term = $request->get('search')['value'];
        $customerIds = app()->user->getSelectedCustomers()->pluck('id')->toArray();

        $shippingCarrierCollection = app('shippingCarrier')->getQuery($filterInputs, $tableColumnName, $sortDirection);

        if (!empty($request->get('from_date'))) {
            $shippingCarrierCollection = $shippingCarrierCollection->where('updated_at', '>=', $request->get('from_date'));
        }

        $shippingCarrierCollection = $shippingCarrierCollection->whereIn('customer_id', $customerIds);

        if ($term) {
            $term = '%' . $term . '%';

            $shippingCarrierCollection->where(function($query) use ($term) {
                $query->where('name', 'like', $term);
                $query->orWhere('carrier_account', 'like', $term);
            });
        }

        $shippingCarrierCollection->orderBy($tableColumnName, $sortDirection);

        if ($request->get('length') && ((int) $request->get('length')) !== -1) {
            $shippingCarrierCollection = $shippingCarrierCollection->skip($request->get('start'))->limit($request->get('length'));
        }

        $shippingCarriers = $shippingCarrierCollection->get();

        $shippingCarrierCollection = ShippingCarrierTableResource::collection($shippingCarriers);

        return response()->json([
            'data' => $shippingCarrierCollection,
            'visibleFields' => app()->editColumn->getVisibleFields('shipping-carrier'),
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request): JsonResponse
    {
        if (!app()->shippingCarrier->store($request)) {
            return response()->json(
                [
                    'message' =>__('There is an error connecting the carrier!')
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return response()->json([
            'message' =>__('Carrier successfully connected!'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param ShippingCarrier $shippingCarrier
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(ShippingCarrier $shippingCarrier)
    {
        $connection = app('tribirdShipping')->getCarrierConnectionDetails($shippingCarrier);

        if (is_null($connection) || $connection['errors']) {
            return redirect()->route('shipping_carrier.index')->withErrors(__('There is an error connecting the carrier!'));
        }

        return view('shipping_carrier.edit', ['shippingCarrier' => $shippingCarrier, 'configuration' => $connection['data']['destination_configuration']]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param ShippingCarrier $shippingCarrier
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, ShippingCarrier $shippingCarrier)
    {
        if (!app()->shippingCarrier->update($request, $shippingCarrier)) {
            return redirect()->back()->withErrors(__('There is an error updating the carrier.'));
        }

        return redirect()->route('shipping_carrier.index')->withStatus(__('Carrier successfully updated.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyRequest $request
     * @param ShippingCarrier $shippingCarrier
     * @return \Illuminate\Http\Response
     */
    public function destroy(DestroyRequest $request, ShippingCarrier $shippingCarrier)
    {
    }

    public function getTribirdCarriers()
    {
        return app('tribirdShipping')->getAvailableIntegrations() ?? [];
    }

    public function getTribirdCarrierConfigurations($type)
    {
        $carrier = app('tribirdShipping')->getIntegration($type);

        return response()->json([
            'data' => $carrier['configuration']
        ]);
    }

    public function disconnectCarrier(DisconnectionRequest $request, ShippingCarrier $shippingCarrier)
    {
        if (!app()->shippingCarrier->disconnectCarrier($request, $shippingCarrier)) {
            return response()->json(
                [
                    'message' =>__('Please type valid text')
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return response()->json([
            'message' =>__('Carrier successfully disconnected!'),
        ]);
    }

    public function connectCarrier(ShippingCarrier $shippingCarrier)
    {
        app()->shippingCarrier->connectCarrier($shippingCarrier);

        return response()->json([
            'message' =>__('Carrier successfully connected!'),
        ]);
    }
}
