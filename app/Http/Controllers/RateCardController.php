<?php

namespace App\Http\Controllers;

use App\Http\Dto\Filters\BillingRatesDataTableDto;
use App\Http\Dto\Filters\OrdersDataTableDto;
use App\Http\Resources\BillingRateTableResource;
use App\Http\Resources\OrderTableResource;
use App\Models\BillingRate;
use App\Models\Customer;
use App\Models\OrderStatus;
use App\Models\ShippingCarrier;
use App\Models\ShippingMethod;
use App\Models\ThreePl;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\RateCard;
use App\Http\Requests\RateCard\StoreRequest;
use App\Http\Requests\RateCard\UpdateRequest;
use App\Http\Requests\RateCard\DestroyRequest;
use App\Http\Resources\RateCardTableResource;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RateCardController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(RateCard::class);
    }
    /**
     * Display a listing of the resource.
     *
     * @return Factory|View
     */
    public function index()
    {
        return view('rate_cards.index');
    }

    public function dataTable(Request $request)
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');

        $sortColumnName = 'rate_cards.id';
        $sortDirection = 'desc';

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $rateCardCollection = RateCard::select('*', 'rate_cards.*')
            ->orderBy($sortColumnName, $sortDirection);

        $term = $request->get('search')['value'];

        if ($term) {
            // TODO: sanitize term
            $term = '%' . $term . '%';

            $rateCardCollection
                ->where('rate_cards.name', 'like', $term)
                ->get();

            $totalRateCardCount = RateCard::count();
            $rateCardCount = $rateCardCollection->count();
        } else {
            $totalRateCardCount = $rateCardCollection->count();
            $rateCardCount = $totalRateCardCount;
        }

        $inventoryLogs = $rateCardCollection->skip($request->get('start'))->limit($request->get('length'))->get();
        $rateCardCollection = RateCardTableResource::collection($inventoryLogs);
        $visibleFields = app('editColumn')->getVisibleFields('rate-cards');

        return response()->json([
            'data' => $rateCardCollection,
            'visibleFields' => $visibleFields,
            'recordsTotal' => $totalRateCardCount,
            'recordsFiltered' => $rateCardCount
        ]);
    }

    public function billingRateDataTable(Request $request)
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'created_at';
        $sortDirection = 'desc';
        $term = $request->get('search')['value'];

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $billingRatesCollection = app('billing_rate')->getQuery();

        $billingRatesCollection = $billingRatesCollection->orderBy(trim($sortColumnName), $sortDirection);

        if ($term) {
            $billingRatesCollection = app('billing_rate')->searchQuery($term, $billingRatesCollection);
        }

        $start = $request->get('start');
        $length = $request->get('length');

        if ($length == -1) {
            $length = 10;
        }

        if ($length) {
            $billingRatesCollection = $billingRatesCollection->skip($start)->limit($length);
        }

        $billingRates = $billingRatesCollection->get();
        $visibleFields = app('editColumn')->getVisibleFields('billing_rates');

        $billingRateCollection = BillingRateTableResource::collection($billingRates);

        return response()->json([
            'data' => $billingRateCollection,
            'visibleFields' => $visibleFields,
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX
        ]);
    }

    public function feesDataTable(Request $request)
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $rateCardId = $request->get('rate_card_id');
        $type = $request->get('type');
        $sortColumnName = 'billing_rates.id';
        $sortDirection = 'desc';
        $term = $request->get('search')['value'];

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $billingRatesCollection = app('billingRate')->getQuery(['rate_card_id' => $rateCardId, 'type' => $type]);

        $billingRatesCollection = $billingRatesCollection->orderBy(trim($sortColumnName), $sortDirection);

        if ($term) {
            $billingRatesCollection = app('billingRate')->searchQuery($term, $billingRatesCollection);
        }

        if ($request->get('length') && ((int) $request->get('length')) !== -1) {
            $billingRatesCollection = $billingRatesCollection->skip($request->get('start'))->limit($request->get('length'));
        }

        $billingRates = $billingRatesCollection->get();
        $visibleFields = app('editColumn')->getVisibleFields('billing_rates');

        $billingRateCollection = BillingRateTableResource::collection($billingRates);

        return response()->json([
            'data' => $billingRateCollection,
            'visibleFields' => $visibleFields,
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Factory|View
     */
    public function create()
    {
        $threePls = [];

        if (auth()->user()->isAdmin()) {
            $threePls = Customer::with('contactInformation')
                ->whereNull('parent_id')
                ->where(['allow_child_customers' => true])
                ->get()
                ->pluck('contactInformation.name', 'id');
        }

        return view('rate_cards.create', ['threePls' => $threePls]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(StoreRequest $request)
    {
        app('rateCard')->store($request);

        return redirect()->route('billings.rate_cards')->withStatus(__('Rate Card successfully created.'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param RateCard $rateCard
     * @return mixed
     */
    public function edit(RateCard $rateCard)
    {
        return view('rate_cards.edit', [
            'rateCard' => $rateCard,
            'threePls' => [],
            'isReadonlyUser' => false,
            'page' => 'manage_rates',
            'data' => [],
            'datatableOrder' => app()->editColumn->getDatatableOrder('rate-cards'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateRequest $request
     * @param RateCard $rateCard
     * @return Response
     */
    public function update(UpdateRequest $request, RateCard $rateCard)
    {
        app('rateCard')->update($request, $rateCard);

        return redirect()->back()->withStatus(__('Rate Card successfully updated.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyRequest $request
     * @param RateCard $rateCard
     * @return Response
     */
    public function destroy(DestroyRequest $request, RateCard $rateCard)
    {
        app('rateCard')->destroy($request, $rateCard);

        return redirect()->route('billings.rate_cards')->withStatus(__('Rate Card successfully deleted.'));
    }

    public function filterCustomers(Request $request)
    {
        return app('rateCard')->filterCustomers($request);
    }

    public function clone($id)
    {
        $rateCard = RateCard::findOrFail($id);
        $clone = $rateCard->replicate();

        $clone->name = $clone->name . " (Copy)";
        $clone->save();

        foreach ($rateCard->billingRates as $rate) {
            $rateClone = $rate->replicate();

            $rateClone->rate_card_id = $clone->id;
            $rateClone->save();
        }

        return redirect()->route('rate_cards.edit', $clone->id);
    }
}
