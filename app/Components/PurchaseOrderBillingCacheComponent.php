<?php

namespace App\Components;

use App\Models\CacheDocuments\PurchaseOrderCacheDocument;
use App\Models\PurchaseOrder;
use App\Traits\CacheDocumentTrait;
use Illuminate\Support\Facades\Log;

class PurchaseOrderBillingCacheComponent
{
    use CacheDocumentTrait;
    public function cachePurchaseOrder(PurchaseOrder $purchaseOrder): ?PurchaseOrderCacheDocument
    {
        Log::channel('billing')->debug('Begins storing purchase order documents',);
        try {
            $cacheDocument = PurchaseOrderCacheDocument::buildFromPurchaseOrder($purchaseOrder);
        } catch (\Exception $e) {
            Log::channel('billing')->debug('[Purchase Order Cache] Failed: ' . $e->getMessage());
            $cacheDocument = null;
        }
        Log::channel('billing')->debug('Finished storing purchase order documents',);
        return $cacheDocument;
    }


    public function updatePurchaseOrderCalculatedBillingRateByBillingRate(
        PurchaseOrderCacheDocument $shipmentCacheDocument,
        array $billingRates
    ): bool
    {
        $shipmentCacheDocument = $this->updateByBillingRates($shipmentCacheDocument, $billingRates);
        return $shipmentCacheDocument->save();
    }

}
