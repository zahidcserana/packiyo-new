<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\DestroyRequest;
use App\Http\Requests\Customer\StoreRequest;
use App\Http\Requests\Customer\UpdateRateCardsRequest;
use App\Http\Requests\Customer\UpdateRequest;
use App\Http\Requests\Customer\UpdateUsersRequest;
use App\Http\Resources\CustomerTableResource;
use App\Models\AddressBook;
use App\Models\RateCard;
use App\Models\Customer;
use App\Models\CustomerUserRole;
use App\Models\Printer;
use App\Models\ShippingBox;
use App\Models\ShippingCarrier;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Customer::class);
        $this->middleware('3pl')->only(['store', 'create']);
    }

    public function index()
    {
        $customer = app('user')->getSelectedCustomers();

        return view('customers.index', [
            'datatableOrder' => app('editColumn')->getDatatableOrder('customers'),
            'customer' => $customer
        ]);
    }

    /**
     * @return Application|Factory|View
     */
    public function create()
    {
        // Admins could have many, non-admins should have one.
        $threePLCustomers = app('user')->getSelectedCustomers()->filter(fn (Customer $customer) => $customer->is3pl());

        return view('customers.create', compact('threePLCustomers'));
    }

    public function dataTable(Request $request): JsonResponse
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'customers.id';
        $sortDirection = 'desc';

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $customerCollection = Customer::join('contact_informations', 'customers.id', '=', 'contact_informations.object_id')
            ->where('contact_informations.object_type', Customer::class)
            ->select('customers.*')
            ->groupBy('customers.id')
            ->orderBy($sortColumnName, $sortDirection);

        $customerCollection = $customerCollection->whereIn('customers.id', app('user')->getSelectedCustomers()->pluck('id')->toArray());

        $term = $request->get('search')['value'];

        if ($term) {
            // TODO: sanitize term
            $term = $term . '%';

            $customerCollection
                ->whereHas('contactInformation', function($query) use ($term) {
                    $query->where('name', 'like', $term)
                        ->orWhere('company_name', 'like', $term)
                        ->orWhere('address', 'like', $term)
                        ->orWhere('address2', 'like', $term)
                        ->orWhere('zip', 'like', $term)
                        ->orWhere('city', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
        }

        $customers = $customerCollection->skip($request->get('start'))->limit($request->get('length'))->get();

        return response()->json([
            'data' => CustomerTableResource::collection($customers),
            'visibleFields' => app('editColumn')->getVisibleFields('customers'),
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX,
        ]);

    }

    /**
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        app('customer')->store($request);

        return response()->json([
            'success' => true,
            'message' => __('Customer successfully created.')
        ]);
    }

    /**
     * @param Request $request
     * @param Customer $customer
     * @return Application|Factory|View
     */
    public function edit(Request $request, Customer $customer)
    {
        $customerUsersIds = $customer->users()->pluck('users.id')->toArray();
        $routeName = $request->route()->getName();
        $allRoles = CustomerUserRole::all();

        $settings = customer_settings($customer->id);

        $printers = Printer::where('customer_id', $customer->id)->pluck('name', 'id');

        $addressBooks = AddressBook::where('customer_id', $customer->id)
            ->when($customer->parent, static function (Builder $query) use ($customer) {
                $query->orWhere('customer_id', $customer->parent->id);
            })->get();

        if ($customer->parent) {
            $warehouses = $customer->parent->warehouses;
        } else {
            $warehouses = $customer->warehouses;
        }

        $data = [
            'customer' => $customer,
            'customerUsersIds' => $customerUsersIds,
            'roles' => $allRoles,
            'warehouses' => $warehouses,
            'settings' => $settings,
            'printers' => $printers,
            'addressBooks' => $addressBooks,
            'lotPriorities' => config('settings.lot_priorities')
        ];

        return match ($routeName) {
            'customer.editUsers' => view('customers.editUsers', $data),
            'customer.cssOverrides' => view('customers.cssOverrides', $data),
            default => view('customers.editCustomer', $data)
        };
    }

    /**
     * @param UpdateRequest $request
     * @param Customer $customer
     * @return JsonResponse
     */
    public function update(UpdateRequest $request, Customer $customer): JsonResponse
    {
        app('customer')->update($request, $customer);

        return response()->json([
            'success' => true,
            'message' => __('Customer successfully updated.')
        ]);
    }

    public function destroy(DestroyRequest $request, Customer $customer)
    {
        app('customer')->destroy($request, $customer);

        return redirect()->route('customer.index')->withStatus(__('Customer successfully deleted.'));
    }

    public function updateGeneralSettings(Customer $customer, Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => __('Settings successfully updated.')
        ]);
    }

    public function detachUser(Customer $customer, User $user)
    {
        $this->authorize('updateUsers', $customer);

        app('customer')->detachUser($customer, $user);

        return redirect()->back()->withStatus(__('Customer successfully updated.'));
    }

    public function updateUsers(UpdateUsersRequest $request, Customer $customer)
    {
        $this->authorize('updateUsers', $customer);

        app('customer')->updateUsers($request, $customer);

        return redirect()->back()->withStatus(__('Customer successfully updated.'));
    }

    public function filterUsers(Request $request, Customer $customer)
    {
        return app('customer')->filterUsers($request, $customer);
    }

    public function getDimensionUnits(Request $request, Customer $customer)
    {
        return app('customer')->getDimensionUnits($customer);
    }

    public function shippingMethods(Request $request, ShippingCarrier $shippingCarrier)
    {
        return app('customer')->getShippingMethods($request, $shippingCarrier);
    }

    /**
     * @param Request $request
     * @param Customer $customer
     * @return Application|Factory|View
     */
    public function editRateCards(Request $request, Customer $customer)
    {
        $rateCards = [];

        if ($customer->parent) {
            $rateCards = RateCard::where3plId($customer->parent_id)->pluck('name', 'id')->toArray();
        }

        // Add empty option as first element, but maintain the keys.
        $rateCards = ['' => ''] + $rateCards;

        return view('customers.rate_cards', [
            'customer' => $customer,
            'rateCards' => $rateCards
        ]);
    }

    /**
     * @param UpdateRateCardsRequest $request
     * @param Customer $customer
     * @return RedirectResponse
     */
    public function updateRateCards(UpdateRateCardsRequest $request, Customer $customer): RedirectResponse
    {
        app('customer')->updateRateCards($request, $customer);

        return redirect()->back()->withStatus(__('Customer rate cards successfully updated'));
    }
}
