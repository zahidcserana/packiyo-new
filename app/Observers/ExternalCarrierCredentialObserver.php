<?php

namespace App\Observers;

use App\Models\ExternalCarrierCredential;

class ExternalCarrierCredentialObserver
{
    /**
     * Handle the ExternalCarrierCredential "saved" event.
     *
     * @param ExternalCarrierCredential $externalCarrierCredential
     * @return void
     */
    public function saved(ExternalCarrierCredential $externalCarrierCredential)
    {
        try {
            app('externalCarrierShipping')->getCarriers($externalCarrierCredential);
        } catch (\Exception $exception) {

        }
    }
}
