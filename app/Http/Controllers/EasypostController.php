<?php

namespace App\Http\Controllers;

use App\Http\Requests\Easypost\CarrierAccount\{CreateAccountRequest, DeleteAccountRequest, UpdateAccountRequest};
use App\Models\{EasypostCredential, ShippingCarrier};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\{Foundation\Application, View\Factory, View\View};

class EasypostController extends Controller
{
    /**
     * @param EasypostCredential $easypostCredential
     * @return Application|Factory|View
     * @throws \JsonException
     */
    public function create(EasypostCredential $easypostCredential): View|Factory|Application
    {
        $carrierTypesResponse = app('easypostShipping')->getCarrierTypes($easypostCredential);
        $carrierTypes = array_column($carrierTypesResponse, 'readable', 'type');
        $carrierCredentials = json_encode(array_column($carrierTypesResponse, 'fields', 'type'), JSON_THROW_ON_ERROR);

        return view('easypost.carriers.create', compact('easypostCredential', 'carrierTypes', 'carrierCredentials'));
    }

    /**
     * @param EasypostCredential $easypostCredential
     * @param ShippingCarrier $carrier
     * @return Application|Factory|View
     * @throws \JsonException
     * @throws AuthorizationException
     */
    public function edit(EasypostCredential $easypostCredential, ShippingCarrier $carrier): View|Factory|Application
    {
        $this->authorize('easypost', $carrier);

        $carrierAccount = app('easypostShipping')->getCarrierAccount($easypostCredential, $carrier->settings['external_carrier_id']);

        return view('easypost.carriers.edit', compact('easypostCredential', 'carrier', 'carrierAccount'));
    }

    /**
     * @param CreateAccountRequest $request
     * @param EasypostCredential $easypostCredential
     * @return mixed
     */
    public function createCarrierAccount(CreateAccountRequest $request, EasypostCredential $easypostCredential)
    {
        try {
            app('easypostShipping')->createCarrierAccount($easypostCredential, $request);

            return redirect()->route('customers.easypost_credentials.index', ['customer' => $easypostCredential->customer])->withStatus(__('Carrier account successfully added'));
        } catch (\Exception $exception) {
            return redirect()->route('customers.easypost_credentials.index', ['customer' => $easypostCredential->customer])->withErrors(__("Carrier account could not be added. :message", ['message' => $exception->getMessage()]));
        }
    }

    /**
     * @param UpdateAccountRequest $request
     * @param EasypostCredential $easypostCredential
     * @param ShippingCarrier $carrier
     * @return mixed
     * @throws AuthorizationException
     */
    public function updateCarrierAccount(UpdateAccountRequest $request, EasypostCredential $easypostCredential, ShippingCarrier $carrier)
    {
        try {
            $this->authorize('easypost', $carrier);

            app('easypostShipping')->updateCarrierAccount($easypostCredential, $request);

            return redirect()->route('customers.easypost_credentials.index', ['customer' => $easypostCredential->customer])->withStatus(__('Carrier account successfully updated'));
        } catch (\Exception $exception) {
            return redirect()->route('customers.easypost_credentials.index', ['customer' => $easypostCredential->customer])->withErrors(__("Carrier account could not be updated. :message", ['message' => $exception->getMessage()]));
        }
    }

    /**
     * @param DeleteAccountRequest $request
     * @param EasypostCredential $easypostCredential
     * @param ShippingCarrier $carrier
     * @return mixed
     * @throws AuthorizationException
     */
    public function deleteCarrierAccount(DeleteAccountRequest $request, EasypostCredential $easypostCredential, ShippingCarrier $carrier)
    {
        try {
            $this->authorize('easypost', $carrier);

            app('easypostShipping')->deleteCarrierAccount($easypostCredential, $request);

            return redirect()->route('customers.easypost_credentials.index', ['customer' => $easypostCredential->customer])->withStatus(__('Carrier account successfully deleted'));
        } catch (\Exception $exception) {
            return redirect()->route('customers.easypost_credentials.index', ['customer' => $easypostCredential->customer])->withErrors(__("Carrier account could not be deleted. :message", ['message' => $exception->getMessage()]));
        }
    }

    /**
     * @param EasypostCredential $easypostCredential
     * @return mixed
     */
    public function getCarrierTypes(EasypostCredential $easypostCredential): mixed
    {
        return app('easypostShipping')->getCarrierTypes($easypostCredential);
    }
}
