<?php

namespace App\Components;

use App\Exceptions\BillingRateException;
use App\Models\BillingRate;
use App\Models\CacheDocuments\PurchaseOrderCacheDocument;
use App\Models\CacheDocuments\PurchaseOrderChargeCacheDocument;
use App\Traits\CacheDocumentTrait;
use App\Traits\MongoBillingCalculatorTrait;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReceivingBillingCalculatorComponent
{
    use MongoBillingCalculatorTrait, CacheDocumentTrait;

    /**
     * @throws BillingRateException
     */
    public function calculate(PurchaseOrderCacheDocument $purchaseOrderBilling, BillingRate $billingRate, bool $recalculate = false): void
    {
        Log::channel('billing')->debug(
            sprintf(
                "[Purchase Order Calculator][[Purchase order cache id: %s][Billing rate id: %s] Start calculating Purchase Order charge document",
                $purchaseOrderBilling->id,
                $billingRate->id
            ));

        $billingRatesCharge = [];
        if (!$recalculate) {
            $billingRateChargeInScope = $this->addBillingRate($billingRate);
        }

        try {
            //#TODO Missing billing rate filter for order and product tags. Should be tackle later on

            $totalQuantityOfItems = $this->calculateTotalQuantity($purchaseOrderBilling);
            PurchaseOrderChargeCacheDocument::build(
                $purchaseOrderBilling,
                $billingRate,
                $billingRate['settings']['fee'],
                $totalQuantityOfItems,
                $this->calculateTotalCharge($totalQuantityOfItems, $billingRate),
                $this->getDescription($purchaseOrderBilling)
            );

            if (!$recalculate) {
                $billingRateChargeInScope['charges'] += 1;
                $billingRatesCharge[] = $billingRateChargeInScope;
            }
        } catch (Throwable $exception) {
            $newException = new BillingRateException($billingRate, $exception);
            Log::channel('billing')->debug(
                sprintf('[Purchase Order Calculator][[Purchase order cache id: %s][Billing rate id: %s] Purchase order charge fail, Error: %s',
                    $purchaseOrderBilling->id,
                    $billingRate->id,
                    $newException->getMessage())
            );
            throw $newException;
        }

        if (!$recalculate) {
            $this->updateCalculatedBillingRate($purchaseOrderBilling, $billingRatesCharge);
        }
        Log::channel('billing')->debug(
            sprintf(
                "[Purchase Order Calculator][[Purchase order cache id: %s][Billing rate id: %s] Finished calculating Purchase Order charge document",
                $purchaseOrderBilling->id,
                $billingRate->id
            )
        );
    }

    private function calculateTotalQuantity(PurchaseOrderCacheDocument $purchaseOrderBilling): float
    {
        return array_reduce($purchaseOrderBilling->items, function ($total, $item) {
            $total += $item['quantity_received'];
            return $total;
        }, 0);
    }

    private function calculateTotalCharge(float $totalItems, BillingRate $billingRate): float
    {
        return ($billingRate['settings']['fee'] * $totalItems);
    }

    private function getDescription(PurchaseOrderCacheDocument $orderCharge): string
    {
        return 'Purchase Order Receiving: ' . $orderCharge['purchase_order_number']; // TODO need confirmation
    }

    private function updateCalculatedBillingRate(
        PurchaseOrderCacheDocument $purchaseOrderCacheDocument,
        array $calculatedBillingRates
    ): void
    {
        $purchaseOrderCacheDocument = $this->updateBillingRates($purchaseOrderCacheDocument, $calculatedBillingRates);
        $purchaseOrderCacheDocument->save();
    }
}
