<?php

namespace App\Observers;

use App\Models\TribirdCredential;

class TribirdCredentialObserver
{
    /**
     * Handle the TribirdCredential "saved" event.
     *
     * @param TribirdCredential $tribirdCredential
     * @return void
     */
    public function saved(TribirdCredential $tribirdCredential)
    {
        try {
            app('tribirdShipping')->getCarriers($tribirdCredential);
        } catch (\Exception $exception) {

        }
    }
}
