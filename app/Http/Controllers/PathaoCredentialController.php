<?php

namespace App\Http\Controllers;

use App\Http\Requests\PathaoCredential\DestroyRequest;
use App\Http\Requests\PathaoCredential\StoreRequest;
use App\Http\Requests\PathaoCredential\UpdateRequest;
use App\Models\Customer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use App\Models\PathaoCredential;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\FetchOne;

class PathaoCredentialController extends Controller
{
    use FetchOne;

    public function __construct()
    {
        $this->authorizeResource(PathaoCredential::class);
    }

    /**
     * @param Customer $customer
     * @return Application|Factory|View|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function index(Customer $customer)
    {
        return view('customers.pathao_credentials', [
            'customer' => $customer->load(['pathaoCredentials'])
        ]);
    }

    /**
     * @param Customer $customer
     * @return Application|Factory|View|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function create(Customer $customer)
    {
        return view('pathao_credentials.create', compact('customer'));
    }

    /**
     * @param StoreRequest $request
     * @param Customer $customer
     * @return mixed
     */
    public function store(StoreRequest $request, Customer $customer)
    {
        app('pathaoCredential')->store($request);

        return redirect()->route('customers.pathao_credentials.index', compact('customer'))->withStatus(__('Pathao credentials successfully created.'));
    }

    /**
     * @param Customer $customer
     * @param PathaoCredential $pathaoCredential
     * @return Application|Factory|View|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function edit(Customer $customer, PathaoCredential $pathaoCredential)
    {
        return view('pathao_credentials.edit', compact('customer', 'pathaoCredential'));
    }

    /**
     * @param UpdateRequest $request
     * @param Customer $customer
     * @param PathaoCredential $pathaoCredential
     * @return mixed
     */
    public function update(UpdateRequest $request, Customer $customer, PathaoCredential $pathaoCredential)
    {
        app('pathaoCredential')->update($request, $pathaoCredential);

        return redirect()->route('customers.pathao_credentials.index', compact('customer'))->withStatus(__('Pathao credentials successfully updated.'));
    }

    /**
     * @param DestroyRequest $request
     * @param Customer $customer
     * @param PathaoCredential $pathaoCredential
     * @return mixed
     */
    public function destroy(DestroyRequest $request, Customer $customer, PathaoCredential $pathaoCredential)
    {
        app('pathaoCredential')->destroy($request, $pathaoCredential);

        return redirect()->route('customers.pathao_credentials.index', compact('customer'))->withStatus(__('Credential successfully deleted.'));
    }
}
