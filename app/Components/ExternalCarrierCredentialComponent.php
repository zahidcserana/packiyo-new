<?php

namespace App\Components;


use App\Http\Requests\ExternalCarrierCredential\DestroyRequest;
use App\Models\Customer;
use App\Models\ExternalCarrierCredential;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class ExternalCarrierCredentialComponent extends BaseComponent
{
    public function store(FormRequest $request)
    {
        $input = $request->validated();

        if (!Arr::has($input, 'customer_id')) {
            $input['customer_id'] = Arr::get($input, 'customer.id');
        }

        $externalCarrierCredential = ExternalCarrierCredential::create($input);

        return $externalCarrierCredential;
    }

    public function update(FormRequest $request, ExternalCarrierCredential $externalCarrierCredential)
    {
        $input = $request->validated();

        if (!Arr::has($input, 'customer_id')) {
            $input['customer_id'] = Arr::get($input, 'customer.id');
        }

        $externalCarrierCredential->update($input);

        return $externalCarrierCredential;
    }

    public function destroy(DestroyRequest $request = null, ExternalCarrierCredential $externalCarrierCredential)
    {
        if (!$externalCarrierCredential) {
            $input = $request->validated();
            $externalCarrierCredential = ExternalCarrierCredential::where('id', $input['id'])->first();
        }

        $response = null;

        if (!empty($externalCarrierCredential)) {
            $externalCarrierCredential->delete();

            $response = collect(['id' => $externalCarrierCredential->id, 'customer_id' => $externalCarrierCredential->customer_id]);
        }

        return $response;
    }
}
