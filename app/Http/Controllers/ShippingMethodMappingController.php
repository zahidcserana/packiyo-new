<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShippingMethodMapping\DestroyRequest;
use App\Http\Requests\ShippingMethodMapping\StoreRequest;
use App\Http\Requests\ShippingMethodMapping\UpdateRequest;
use App\Http\Resources\ShippingMethodMappingTableResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ShippingMethodMapping;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Request;

class ShippingMethodMappingController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ShippingMethodMapping::class);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('shipping_method_mappings.index', [
            'datatableOrder' => app('editColumn')->getDatatableOrder('shipping_method_mappings'),
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function dataTable(Request $request): JsonResponse
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'orders.ordered_at';
        $sortDirection = 'desc';
        $customers = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $shippingMethodMappingCollection = Order::whereBetween('orders.ordered_at', [Carbon::now()->subMonths(3), Carbon::now()])
            ->leftJoin('order_channels', 'order_channels.id', '=', 'orders.order_channel_id')
            ->leftJoin('shipping_method_mappings', function ($join) {
                $join->on('orders.customer_id', '=', 'shipping_method_mappings.customer_id')
                    ->on('orders.shipping_method_name', '=', 'shipping_method_mappings.shipping_method_name')
                    ->whereNull('shipping_method_mappings.deleted_at');
            })
            ->leftJoin('shipping_methods', 'shipping_method_mappings.shipping_method_id', '=', 'shipping_methods.id')
            ->leftJoin('shipping_carriers', 'shipping_methods.shipping_carrier_id', '=', 'shipping_carriers.id')
            ->leftJoin('shipping_methods as return_shipping_methods', 'shipping_method_mappings.return_shipping_method_id', '=', 'return_shipping_methods.id')
            ->leftJoin('shipping_carriers as return_shipping_carriers', 'return_shipping_methods.shipping_carrier_id', '=', 'return_shipping_carriers.id')
            ->whereIn('orders.customer_id', $customers)
            ->where('orders.shipping_method_name', '<>', '')
            ->groupBy('orders.shipping_method_name')
            ->select('orders.*')
            ->orderBy($sortColumnName, $sortDirection);

        $term = $request->get('search')['value'];

        if ($term) {
            $term .= '%';

            $shippingMethodMappingCollection->where(function ($q) use ($term) {
                $q->where('orders.shipping_method_name', 'like', $term)
                    ->orWhereHas('shippingMethodMapping.shippingMethod', function ($query) use ($term) {
                        $query->where('name', 'like', $term);
                    })
                    ->orWhereHas('shippingMethodMapping.shippingMethod.shippingCarrier', function ($query) use ($term) {
                        $query->where('name', 'like', $term);
                    });
            });
        }

        if ($request->get('length') && ((int) $request->get('length')) !== -1) {
            $shippingMethodMappingCollection = $shippingMethodMappingCollection->skip($request->get('start'))->limit($request->get('length'));
        }

        $shippingMethodMappings = $shippingMethodMappingCollection->get();

        $visibleFields = app('editColumn')->getVisibleFields('shipping_method_mappings');

        return response()->json([
            'data' => ShippingMethodMappingTableResource::collection($shippingMethodMappings),
            'visibleFields' => $visibleFields,
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create($shippingMethodName = null)
    {
        if (!empty($shippingMethodName)) {
            $shippingMethodName = base64_decode($shippingMethodName);
        }

        return view('shipping_method_mappings.create', compact('shippingMethodName'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return Response
     */
    public function store(StoreRequest $request)
    {
        app('shippingMethodMapping')->store($request);

        return redirect()->route('shipping_method_mapping.index')->withStatus(__('Shipping method successfully created.'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param ShippingMethodMapping $shippingMethodMapping
     * @return Application|Factory|View
     */
    public function edit(ShippingMethodMapping $shippingMethodMapping)
    {
        $shippingMethodName = isset($shippingMethodMapping->shippingMethod) ? $shippingMethodMapping->shippingMethod->getCarrierNameAndNameAttribute() : null;
        $shippingMethodId = isset($shippingMethodMapping->shippingMethod) ? $shippingMethodMapping->shippingMethod->id : null;

        if (is_null($shippingMethodName) && array_key_exists($shippingMethodMapping->type, ShippingMethodMapping::CHEAPEST_SHIPPING_METHODS)) {
            $shippingMethodName = ShippingMethodMapping::CHEAPEST_SHIPPING_METHODS[$shippingMethodMapping->type];
            $shippingMethodId = $shippingMethodMapping->type;
        }

        return view('shipping_method_mappings.edit', compact('shippingMethodMapping', 'shippingMethodName', 'shippingMethodId'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateRequest $request
     * @param ShippingMethodMapping $shippingMethodMapping
     * @return RedirectResponse
     */
    public function update(UpdateRequest $request, ShippingMethodMapping $shippingMethodMapping): RedirectResponse
    {
        app('shippingMethodMapping')->update($request, $shippingMethodMapping);

        return redirect()->back()->withStatus(__('Shipping method successfully updated.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyRequest $request
     * @param ShippingMethodMapping $shippingMethodMapping
     * @return RedirectResponse
     */
    public function destroy(DestroyRequest $request, ShippingMethodMapping $shippingMethodMapping): RedirectResponse
    {
        app('shippingMethodMapping')->destroy($request, $shippingMethodMapping);

        return redirect()->back()->withStatus(__('Shipping method successfully deleted.'));

    }

    public function filterCustomers(Request $request)
    {
        return app('shippingMethodMapping')->filterCustomers($request);
    }

    public function filterShippingMethods(Request $request, Customer $customer = null): JsonResponse
    {
        $includeCheapestShippingMethods = $request->get('include_cheapest') ?? false;

        $shippingMethods = app('shippingMethodMapping')->filterShippingMethods($customer, $request->get('term'), $includeCheapestShippingMethods);
        $results[] = [
            'id' =>'generic',
            'text' => __('Generic')
        ];

        foreach ($shippingMethods as $shippingMethod) {
            if ($shippingMethod instanceof ShippingMethodMapping) {
                $results[] = [
                    'id' => $shippingMethod->type,
                    'text' => $shippingMethod->shipping_method_name
                ];
            } else {
                $results[] = [
                    'id' => $shippingMethod->id,
                    'text' => $shippingMethod->shippingCarrier->getNameAndIntegrationAttribute() . ' - ' . $shippingMethod->name ?? $shippingMethod->shipping_method_name
                ];
            }
        }

        return response()->json([
            'results' => $results
        ]);
    }
}
