<?php

namespace App\Http\Controllers;

use App\Http\Requests\SteadfastCredential\DestroyRequest;
use App\Http\Requests\SteadfastCredential\StoreRequest;
use App\Http\Requests\SteadfastCredential\UpdateRequest;
use App\Models\Customer;
use App\Models\SteadfastCredential;
use Illuminate\Contracts\View\View;

class SteadfastCredentialController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(SteadfastCredential::class);
    }

    public function index(Customer $customer): View
    {
        return view('customers.steadfast_credentials', [
            'customer' => $customer->load(['steadfastCredentials']),
        ]);
    }

    public function create(Customer $customer): View
    {
        return view('steadfast_credentials.create', compact('customer'));
    }

    public function store(StoreRequest $request, Customer $customer): mixed
    {
        app('steadfastCredential')->store($request, $customer);

        return redirect()
            ->route('customers.steadfast_credentials.index', compact('customer'))
            ->withStatus(__('Steadfast credentials successfully created.'));
    }

    public function edit(Customer $customer, SteadfastCredential $steadfastCredential): View
    {
        return view('steadfast_credentials.edit', compact('customer', 'steadfastCredential'));
    }

    public function update(UpdateRequest $request, Customer $customer, SteadfastCredential $steadfastCredential): mixed
    {
        app('steadfastCredential')->update($request, $steadfastCredential);

        return redirect()
            ->route('customers.steadfast_credentials.index', compact('customer'))
            ->withStatus(__('Steadfast credentials successfully updated.'));
    }

    public function destroy(DestroyRequest $request, Customer $customer, SteadfastCredential $steadfastCredential): mixed
    {
        app('steadfastCredential')->destroy($request, $steadfastCredential);

        return redirect()
            ->route('customers.steadfast_credentials.index', compact('customer'))
            ->withStatus(__('Credential successfully deleted.'));
    }
}
