<?php

namespace App\Components\Invoice\Facades;

use App\Components\PurchaseOrderBillingCacheComponent;
use App\Components\ReceivingBillingCalculatorComponent;
use App\Components\ShipmentBillingCacheService;
use App\Models\BillingRate;
use App\Models\CacheDocuments\InvoiceCacheDocument;
use App\Models\CacheDocuments\PurchaseOrderCacheDocument;
use App\Models\CacheDocuments\PurchaseOrderChargeCacheDocument;
use App\Models\PurchaseOrder;
use App\Traits\CacheDocumentTrait;
use App\Traits\InvoiceBillableOperationTrait;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MongoInvoiceReceivingFacade
{
    use CacheDocumentTrait, InvoiceBillableOperationTrait;

    const CHUNK_SIZE = 1000;
    private ReceivingBillingCalculatorComponent $receivingBillingCalculatorComponent;

    public function __construct(
        ReceivingBillingCalculatorComponent $receivingBillingCalculatorComponent
    )
    {
        $this->receivingBillingCalculatorComponent = $receivingBillingCalculatorComponent;
    }

    public function generateInvoiceItemForPurchaseOver(InvoiceCacheDocument $invoiceCacheDocument, array $invoiceBillingRates): bool
    {
        $result = true;
        $query = PurchaseOrder::where(['customer_id' => $invoiceCacheDocument->customer_id])
            ->whereBetween('closed_at', [
                Carbon::parse($invoiceCacheDocument->period_start)->startOfDay(),
                Carbon::parse($invoiceCacheDocument->period_end)->endOfDay()
            ]);

        Log::channel('billing')
            ->debug(
                sprintf("[Invoice id: %s] Amount of purchase orders: %s to be process for invoice",
                    $invoiceCacheDocument->id, $query->count())
            );

        $query->chunkById(self::CHUNK_SIZE, function ($purchaseOrders) use ($invoiceCacheDocument, $invoiceBillingRates, &$billableOperationWithError, &$result) {
            $billableOperationIds = $purchaseOrders->pluck('id')->toArray();
            $query = PurchaseOrderCacheDocument::whereIn('purchase_order_id', $billableOperationIds);
            if ($query->count() == 0) {
                Log::channel('billing')->info(
                    sprintf("[Invoice id: %s] Purchase documents documents missing during generation of invoice items",
                        $invoiceCacheDocument->id)
                );
                $result = false;
                return;
            }

            $query->chunk(self::CHUNK_SIZE, function ($billableOperationDocuments) use ($invoiceCacheDocument, &$invoiceBillingRates) {
                /* @var PurchaseOrderCacheDocument $document */
                foreach ($billableOperationDocuments as $document) {
                    Log::channel('billing')->debug(
                        sprintf(
                            "[Invoice id: %s][Purchase Order Cache Id: %s] Start processing document ",
                            $invoiceCacheDocument->id,
                            $document->id
                        )
                    );
                    //retrieve charge documents
                    $chargeDocuments = [];
                    $billingRatesToAddBillableOperationDoc = [];
                    $billingRateToDiscardFromCharges = [];
                    $billingRateToUpdateTimeStamp = [];
                    $chargesDocumentsToAdd = [];
                    $chargesDocumentsToDelete = [];
                    $chargesDTOs = [];

                    $chargeDocuments = $this->getChargeDocuments($document, $chargeDocuments);
                    $docBillingRates = collect($document->getCalculatedBillingRates());
                    foreach ($invoiceBillingRates as $billingRate) {
                        $billingRateToCalculate = null;
                        $billingRateInScope = $docBillingRates->where('billing_rate_id', $billingRate->id)->first();
                        if (!$billingRateInScope) {
                            // is not in calculated billing rates
                            $billingRateToCalculate = $billingRate;
                        }

                        if (!empty($billingRateToCalculate)) {
                            //calculate missing billing rate charges
                            Log::channel('billing')->debug(
                                sprintf("[Invoice id: %s][Purchase Order Cache Id: %s] Generating new charges by missing billing rate id: %s",
                                    $invoiceCacheDocument->id,
                                    $document->id,
                                    $billingRateToCalculate->id)
                            );
                            $chargesDocumentsToAdd = $this->calculateMissingBillingRateCharges(
                                $document,
                                $billingRateToCalculate,
                                $chargeDocuments[$billingRate->type],
                                $chargesDocumentsToAdd
                            );
                            $chargesResultCount = 0;
                            foreach ($chargesDocumentsToAdd as $chargeToAdd) {
                                $chargesResultCount += $chargeToAdd->count();
                            }

                            // gets the difference between result and original
                            $billingRatesToAddBillableOperationDoc[] = [
                                'billing_rate_id' => $billingRateToCalculate->id,
                                'calculated_at' => $billingRate->updated_at->toIso8601String(),
                                'charges' => $chargesResultCount
                            ];
                            continue;
                        }

                        if ($this->isBillingRateUpdated($billingRateInScope, $billingRate)) {
                            // is not less
                            $billingRateToCalculate = $billingRate;
                            $billingRateToDiscardFromCharges[] = $billingRate;
                            $billingRateToUpdateTimeStamp[] = $billingRate;
                        }

                        if (!empty($billingRateToCalculate)) {
                            Log::channel('billing')->debug(
                                sprintf(
                                    "[Invoice id: %s][Purchase Order Cache Id: %s] Billing rate id: %s updated, generating new charges",
                                    $invoiceCacheDocument->id,
                                    $document->id,
                                    $billingRateToCalculate->id
                                ));
                            //recalculate billing rate charges
                            $chargesDocumentsToAdd = $this->calculateMissingBillingRateCharges(
                                $document,
                                $billingRateToCalculate,
                                $chargeDocuments[$billingRate->type],
                                $chargesDocumentsToAdd
                            );
                            continue;
                        }

                        if (!$this->getChargesByBillingRate($chargeDocuments, $billingRate)->count() == $billingRateInScope['charges']) {
                            //charges dont match
                            $billingRateToCalculate = $billingRate;
                            $billingRateToDiscardFromCharges[] = $billingRate;
                        }

                        if (!empty($billingRateToCalculate)) {
                            Log::channel('billing')->debug(
                                sprintf(
                                    "[Invoice id: %s][Purchase Order Cache Id: %s] Generating new charges by billing rate id %s charges count mismatch",
                                    $invoiceCacheDocument->id,
                                    $document->id,
                                    $billingRateToCalculate->id
                                ));
                            //recalculate billing rate charges
                            $chargesDocumentsToAdd = $this->calculateMissingBillingRateCharges(
                                $document,
                                $billingRateToCalculate,
                                $chargeDocuments[$billingRate->type],
                                $chargesDocumentsToAdd
                            );
                        }
                    }
                    //remove elements from memory
                    $this->removeChargesByBillingRates($billingRateToDiscardFromCharges, $chargeDocuments, $chargesDocumentsToDelete);
                    $charges = $this->getCharges($chargesDocumentsToAdd, $chargeDocuments, $chargesDTOs);

                    //create invoice line items
                    foreach ($charges as $charge) {
                        $charge['invoice_id'] = $invoiceCacheDocument['id'];
                        app('invoice')->createInvoiceLineItemOnTheFly($charge);
                    }

                    $this->updateCacheDocuments($billingRatesToAddBillableOperationDoc, $document, $chargesDocumentsToDelete, $billingRateToUpdateTimeStamp);
                    Log::channel('billing')->debug(
                        sprintf(
                            "[Invoice id: %s][Purchase Order Cache Id: %s] Finish processing document ",
                            $invoiceCacheDocument->id,
                            $document->id
                        )
                    );
                }
            });
        });

        return $result;
    }

    private function getChargeDocuments(PurchaseOrderCacheDocument $record, array $chargeDocs): array
    {
        $chargeDocs[BillingRate::PURCHASE_ORDER] = PurchaseOrderChargeCacheDocument::where('purchase_order_id', $record->purchase_order_id)->get();
        return $chargeDocs;
    }

    private function calculateMissingBillingRateCharges(
        PurchaseOrderCacheDocument $record,
        BillingRate $billingRateToCalculate,
        Collection $items,
        array $chargesToAdd
    ): array
    {
        $allCharges = $this->calculateBillingRate($record, $billingRateToCalculate);
        $chargesToAdd[] = $allCharges->diff($items);
        return $this->filterEmptyCharges($chargesToAdd);
    }

    private function calculateBillingRate(
        PurchaseOrderCacheDocument $purchaseDocument,
        BillingRate $billingRate
    )
    {
        $result = null;
        $this->receivingBillingCalculatorComponent->calculate($purchaseDocument, $billingRate, true);

        switch ($billingRate->type) {
            case BillingRate::PURCHASE_ORDER:
                $result = PurchaseOrderChargeCacheDocument::where('purchase_order_id', $purchaseDocument->purchase_order_id)->get(); // gets everything
                break;
            default:
                //add new billing rates for receiving
        }

        return $result;
    }

    private function getCharges(
        array $chargesToAdd,
        array $chargeDocs,
        array $chargesDTOs
    ): array
    {
        foreach ($chargesToAdd as $chargeToAdd) {
            // if add it more bill rates consider here
            $chargeDocs[BillingRate::PURCHASE_ORDER] = $chargeDocs[BillingRate::PURCHASE_ORDER]->merge($chargeToAdd);
        }

        foreach ($chargeDocs as $key => $value) {
            $chargesDTOs[$key] = $value->map(function ($el) {
                return $el->getCharges();
            });
        }

        return $this->formatCharges(
            $chargesDTOs[BillingRate::PURCHASE_ORDER]
        );
    }

    /**
     * @param Collection $charges
     * @return array
     */
    private function formatCharges(Collection $charges): array
    {
        $charges = [
            array_merge(...$charges)
        ];

        $charges = $this->billableOperationFlattenArray($charges);
        return array_filter($charges, fn($element) => !empty($element));
    }

    private function updateCacheDocuments(
        array $billingRateToAdd,
        PurchaseOrderCacheDocument $purchaseOrderCacheDocument,
        array $chargesToDelete,
        array $billingRateToUpdateTimeStamp
    ): void
    {
        //add to shipment cache billing rates calculated
        if (!empty($billingRateToAdd)) {
            $this->updateBillingRates($purchaseOrderCacheDocument, $billingRateToAdd);
            $purchaseOrderCacheDocument->save();
        }

        if (!empty($billingRateToUpdateTimeStamp)) {
            app(PurchaseOrderBillingCacheComponent::class)->updatePurchaseOrderCalculatedBillingRateByBillingRate(
                $purchaseOrderCacheDocument,
                $billingRateToUpdateTimeStamp
            );
        }

        //deletes charges marked for deletion
        if (!empty($chargesToDelete)) {
            //delete charges in
            foreach ($chargesToDelete as $charge) $charge->delete();
        }
    }
}
