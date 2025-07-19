<?php

namespace App\Traits;

use App\Models\BillingRate;
use App\Models\CacheDocuments\CacheDocumentInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait CacheDocumentTrait
{
    public function updateBillingRates(
        CacheDocumentInterface $cacheDocument,
        array $calculatedBillingRates
    ): CacheDocumentInterface
    {
        foreach ($calculatedBillingRates as $inScopeCalculatedBillingRate) {
            $docCalculatedBillingRates = $cacheDocument->getCalculatedBillingRates();
            $countShipmentCalculatedBillingRate = count($docCalculatedBillingRates);
            $currentIndex = 0;
            if (empty($docCalculatedBillingRates)) {
                $cacheDocument->calculated_billing_rates = $calculatedBillingRates;
            } else {

                foreach ($docCalculatedBillingRates as $docCalculatedBillingRate) {
                    $currentIndex++;
                    if ($inScopeCalculatedBillingRate['billing_rate_id'] == $docCalculatedBillingRate['billing_rate_id']) {
                        Log::channel('billing')->debug(
                            sprintf("[CacheDocumentTrait] Updating Billing rate id: %s from calculated billing rates, for document id: %s",
                                $inScopeCalculatedBillingRate['billing_rate_id'],
                                $cacheDocument->id
                            )
                        );
                        $inScopeCalculatedBillingRate['charges'] += $docCalculatedBillingRate['charges'];
                        $inScopeCalculatedBillingRate['calculated_at'] = $docCalculatedBillingRate['calculated_at'];
                        continue;
                    }

                    if ($currentIndex == $countShipmentCalculatedBillingRate) {
                        $toAddBillingRate[] = $inScopeCalculatedBillingRate;
                    }
                }

                if (!empty($toAddBillingRate)) {
                    $cacheDocument->calculated_billing_rates = array_merge($cacheDocument->calculated_billing_rates, $toAddBillingRate);
                }
            }
        }
        return $cacheDocument;
    }

    public function updateByBillingRates(
        CacheDocumentInterface $cacheDocument,
        array $billingRates
    ): CacheDocumentInterface
    {

        $docCalculatedBillingRates = $cacheDocument->getCalculatedBillingRates();

        /** @var BillingRate $inScopeCalculatedBillingRate */
        foreach ($billingRates as $inScopeCalculatedBillingRate) {
            foreach ($docCalculatedBillingRates as $index => $docCalculatedBillingRate) {
                if ($inScopeCalculatedBillingRate->id == $docCalculatedBillingRate['billing_rate_id']) {
                    Log::channel('billing')->debug(
                        sprintf(
                            "[CacheDocumentTrait][Cache Document Id: %s] Updating TimeStamp from Billing rate id: %s for calculated billing rates",
                            $cacheDocument->id,
                            $inScopeCalculatedBillingRate->id,
                        )
                    );

                    //for now just timestamp is need it for update
                    $docCalculatedBillingRates[$index]['calculated_at'] = $inScopeCalculatedBillingRate->updated_at->toIso8601String();
                }
            }
        }

        $cacheDocument->calculated_billing_rates = $docCalculatedBillingRates;
        return $cacheDocument;
    }
}
