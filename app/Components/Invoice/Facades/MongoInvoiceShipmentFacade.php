<?php

namespace App\Components\Invoice\Facades;

use App\Components\FulfillmentBillingCalculatorService;
use App\Components\ShipmentBillingCacheService;
use App\Models\BillingRate;
use App\Models\CacheDocuments\InvoiceCacheDocument;
use App\Models\CacheDocuments\PackagingRateShipmentCacheDocument;
use App\Models\CacheDocuments\PickingBillingRateShipmentCacheDocument;
use App\Models\CacheDocuments\ShipmentCacheDocument;
use App\Models\CacheDocuments\ShippingLabelRateShipmentCacheDocument;
use App\Models\Shipment;
use App\Traits\InvoiceBillableOperationTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MongoInvoiceShipmentFacade
{
    use InvoiceBillableOperationTrait;

    const CHUNK_SIZE = 1000;
    const FULFILLMENT_CHARGES_TYPE = [
        PickingBillingRateShipmentCacheDocument::class => BillingRate::SHIPMENTS_BY_PICKING_RATE_V2,
        ShippingLabelRateShipmentCacheDocument::class => BillingRate::SHIPMENTS_BY_SHIPPING_LABEL,
        PackagingRateShipmentCacheDocument::class => BillingRate::PACKAGING_RATE,
    ];
    private FulfillmentBillingCalculatorService $fulfillmentBillingCalculator;

    public function __construct(
        FulfillmentBillingCalculatorService $fulfillmentBillingCalculator
    )
    {
        $this->fulfillmentBillingCalculator = $fulfillmentBillingCalculator;
    }

    public function generateInvoiceItemForShipments(InvoiceCacheDocument $invoiceCacheDocument, array $invoiceBillingRates): bool
    {
        $result = true;

        $ordersBilledAlready = [];
        // TODO: This query is loading into memory the IDs of potentially several hundred thousand numbers. We need to rethink this.
        $query = Shipment::whereHas('order', static function (Builder $query) use ($invoiceCacheDocument) {
            $query->where('customer_id', $invoiceCacheDocument->customer_id);
        })->whereBetween('created_at', [
            Carbon::parse($invoiceCacheDocument->period_start)->startOfDay(),
            Carbon::parse($invoiceCacheDocument->period_end)->endOfDay()
        ])->whereNull('voided_at');

        Log::channel('billing')
            ->debug(
                sprintf("[Invoice id: %s] Amount of shipments :%s to be process for invoice",
                    $invoiceCacheDocument->id, $query->count())
            );

        $query->chunkById(self::CHUNK_SIZE, function ($shipments) use ($invoiceCacheDocument, $invoiceBillingRates, &$result, &$ordersBilledAlready) {
            $shipments = $shipments->filter(function (Shipment $shipment) use ($ordersBilledAlready) {
                return !in_array($shipment->order->id, $ordersBilledAlready);
            });
            $billableOperationIds = $shipments->pluck('id')->toArray();

            $query = ShipmentCacheDocument::whereIn('shipments.id', $billableOperationIds);

            if ($query->count() == 0) {
                Log::channel('billing')->info(
                    sprintf("[Invoice id: %s] Shipment documents missing during generation of invoice items",
                        $invoiceCacheDocument->id)
                );
                $result = false;
                return;
            }

            $query->chunkById(self::CHUNK_SIZE, function ($billableOperationDocuments) use ($invoiceCacheDocument, &$invoiceBillingRates, &$ordersBilledAlready) {
                /* @var ShipmentCacheDocument $document */
                foreach ($billableOperationDocuments as $document) {
                    Log::channel('billing')->debug(
                        sprintf(
                            "[Invoice id: %s][Shipment Cache Id: %s] Start processing document ",
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
                    $charges = [];

                    $chargeDocuments = $this->getChargeDocuments($document, $chargeDocuments, $invoiceBillingRates);
                    $docBillingRates = collect($document->getCalculatedBillingRates());
                    foreach ($invoiceBillingRates as $billingRate) {
                        $billingRateToCalculate = null;
                        $billingRateInScope = $docBillingRates->where('billing_rate_id', $billingRate->id)->first();
                        if (!$billingRateInScope) {
                            // is not in calculated billing rates
                            $billingRateToCalculate = $billingRate;
                        }

                        if (!empty($billingRateToCalculate)) {
                            Log::channel('billing')->debug(
                                sprintf(
                                    "[Invoice id: %s][Shipment Cache Id: %s] Generating new charges by missing billing rate id: %s",
                                    $invoiceCacheDocument->id,
                                    $document->id,
                                    $billingRateToCalculate->id
                                )
                            );
                            //calculate missing billing rate charges, no recalculation
                            list($chargesResult, $chargesDocumentsToAdd) = $this->calculateMissingBillingRateCharges(
                                $document,
                                $billingRateToCalculate,
                                $chargeDocuments[$billingRate->type],
                                $chargesDocumentsToAdd
                            );
                            $chargesResultCount = $chargesResult->count();
                            if ($chargesResult->first() instanceof PickingBillingRateShipmentCacheDocument) {
                                $chargesResultCount = count($chargesResult->first()->getCharges());
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
                                    "[Invoice id: %s][Shipment Cache Id: %s] Billing rate id: %s updated, generating new charges",
                                    $invoiceCacheDocument->id,
                                    $document->id,
                                    $billingRateToCalculate->id
                                )
                            );
                            //recalculate billing rate charges, recalculating
                            list($chargesResult, $chargesDocumentsToAdd) = $this->calculateMissingBillingRateCharges(
                                $document,
                                $billingRateToCalculate,
                                $chargeDocuments[$billingRate->type],
                                $chargesDocumentsToAdd,
                                true
                            );
                            continue;
                        }

                        $evaluateCharges = $this->getChargesByBillingRate($chargeDocuments, $billingRate);
                        $chargesResultCount = $evaluateCharges->count();
                        if ($evaluateCharges->first() instanceof PickingBillingRateShipmentCacheDocument) {
                            $chargesResultCount = count($evaluateCharges->first()->getCharges());
                        }

                        if (!$chargesResultCount == $billingRateInScope['charges']) {
                            //charges dont match
                            $billingRateToCalculate = $billingRate;
                            $billingRateToDiscardFromCharges[] = $billingRate;
                        }

                        if (!empty($billingRateToCalculate)) {
                            Log::channel('billing')->debug(
                                sprintf(
                                    "[Invoice id: %s][Shipment Cache Id: %s] Generating new charges by billing rate id %s charges count mismatch",
                                    $invoiceCacheDocument->id,
                                    $document->id,
                                    $billingRateToCalculate->id
                                )
                            );
                            //recalculate billing rate charges, no recalculating
                            list($chargesResult, $chargesDocumentsToAdd) = $this->calculateMissingBillingRateCharges(
                                $document,
                                $billingRateToCalculate,
                                $chargeDocuments[$billingRate->type],
                                $chargesDocumentsToAdd
                            );
                        }
                    }
                    //remove elements from memory
                    $this->removeChargesByBillingRates($billingRateToDiscardFromCharges, $chargeDocuments, $chargesDocumentsToDelete);

                    $chargeDocuments = $this->filterShipmentsByOrdersWithMultipleShipments($chargeDocuments, $ordersBilledAlready);
                    list($chargeDocuments, $charges) = $this->getCharges($chargesDocumentsToAdd, $chargeDocuments, $chargesDTOs);

                    //create invoice line items
                    foreach ($charges as $charge) {
                        $charge['invoice_id'] = $invoiceCacheDocument['id'];
                        app('invoice')->createInvoiceLineItemOnTheFly($charge);
                    }

                    $this->updateCacheDocuments($billingRatesToAddBillableOperationDoc, $document, $chargesDocumentsToDelete, $billingRateToUpdateTimeStamp);
                    Log::channel('billing')->debug(
                        sprintf(
                            "[Invoice id: %s][Shipment Cache Id: %s] Finish processing document ",
                            $invoiceCacheDocument->id,
                            $document->id
                        )
                    );
                }
            });

            $ordersBilledAlready = $this->addOrdersWithMultipleShipments($shipments, $ordersBilledAlready);
        });
        return $result;
    }

    private function calculateBillingRate(ShipmentCacheDocument $shipmentCacheDocument, BillingRate $billingRate, bool $recalculate = false): Collection
    {
        $result = null;

        $this->fulfillmentBillingCalculator->calculate($shipmentCacheDocument, $billingRate, $recalculate);

        $ids = collect($shipmentCacheDocument->getShipments())->map(fn($element) => $element['id']);
        switch ($billingRate->type) {
            case BillingRate::SHIPMENTS_BY_SHIPPING_LABEL:
                //continue here
                $result = ShippingLabelRateShipmentCacheDocument::whereIn('shipment_id', $ids)->where('billing_rate.id', $billingRate->id)->get(); // gets everything
                break;
            case BillingRate::SHIPMENTS_BY_PICKING_RATE_V2:
                $result = PickingBillingRateShipmentCacheDocument::whereIn('shipment_id', $ids)->where('billing_rate.id', $billingRate->id)->get();
                break;
            case BillingRate::PACKAGING_RATE:
                $result = PackagingRateShipmentCacheDocument::whereIn('shipment_id', $ids)->where('billing_rate.id', $billingRate->id)->get();
                break;
        }

        return $result;
    }

    /**
     * @param mixed $shippingCharges
     * @param mixed $pickingCharges
     * @param mixed $packageCharges
     * @return array
     */
    private function formatCharges(mixed $shippingCharges, mixed $pickingCharges, mixed $packageCharges): array
    {
        $charges = [
            array_merge(...$shippingCharges),
            array_merge(...$pickingCharges),
            array_merge(...$packageCharges)
        ];

        $charges = $this->billableOperationFlattenArray($charges);
        return array_filter($charges, fn($element) => !empty($element));
    }

    /**
     * @param ShipmentCacheDocument $record
     * @param array $chargeDocs
     * @param array $invoiceBillingRates
     * @return array
     */
    private function getChargeDocuments(ShipmentCacheDocument $record, array $chargeDocs, array $invoiceBillingRates): array
    {
        $hasShippingRates = false;
        $hasPickingRates = false;
        $hasPackagingRates = false;

        /** @var BillingRate $billingRate */
        foreach ($invoiceBillingRates as $billingRate) {
            if ($billingRate->type == BillingRate::SHIPMENTS_BY_SHIPPING_LABEL) {
                $hasShippingRates = true;
                $shippingBillingRateIds[] = $billingRate->id;
            } elseif ($billingRate->type == BillingRate::SHIPMENTS_BY_PICKING_RATE_V2) {
                $hasPickingRates = true;
                $pickingBillingRateIds[] = $billingRate->id;
            } elseif ($billingRate->type == BillingRate::PACKAGING_RATE) {
                $hasPackagingRates = true;
                $packageBillingRateIds[] = $billingRate->id;
            }
        }

        $shipmentIds = collect($record->getShipments())->map(fn($el) => $el['id']); //this is normally one but we need to consider multiple
        $chargeDocs[self::FULFILLMENT_CHARGES_TYPE[ShippingLabelRateShipmentCacheDocument::class]] = $hasShippingRates
            ? ShippingLabelRateShipmentCacheDocument::whereIn('shipment_id', $shipmentIds)->whereNull('deleted_at')->whereIn('billing_rate.id', $shippingBillingRateIds)->get()
            : collect();
        $chargeDocs[self::FULFILLMENT_CHARGES_TYPE[PickingBillingRateShipmentCacheDocument::class]] = $hasPickingRates
            ? PickingBillingRateShipmentCacheDocument::whereIn('shipment_id', $shipmentIds)->whereNull('deleted_at')->whereIn('billing_rate.id', $pickingBillingRateIds)->get()
            : collect();
        $chargeDocs[self::FULFILLMENT_CHARGES_TYPE[PackagingRateShipmentCacheDocument::class]] = $hasPackagingRates ?
            PackagingRateShipmentCacheDocument::whereIn('shipment_id', $shipmentIds)->whereNull('deleted_at')->whereIn('billing_rate.id', $packageBillingRateIds)->get()
            : collect();

        return $chargeDocs;
    }

    /**
     * @param ShipmentCacheDocument $record
     * @param mixed $billingRateToCalculate
     * @param $items
     * @param array $chargesToAdd
     * @param bool $recalculate
     * @return array
     */
    private function calculateMissingBillingRateCharges(
        ShipmentCacheDocument $document,
        mixed $billingRateToCalculate,
        $items,
        array $chargesToAdd,
        bool $recalculate = false
    ): array
    {
        Log::channel('billing')->debug(
            sprintf("[Shipment Cache id: %s] Generate fulfillment charges for document",
                $document->id
            ));
        $result = $this->calculateBillingRate($document, $billingRateToCalculate, $recalculate);
        $chargesToAdd[] = $result->diff($items);
        $chargesToAdd = $this->filterEmptyCharges($chargesToAdd);
        return array($result, $chargesToAdd);
    }

    /**
     * @param array $billingRateToAdd
     * @param ShipmentCacheDocument $record
     * @param array $chargesToDelete
     * @param array $billingRateToUpdateTimeStamp
     */
    private function updateCacheDocuments(
        array $billingRateToAdd,
        ShipmentCacheDocument $record,
        array $chargesToDelete,
        array $billingRateToUpdateTimeStamp): void
    {
        //add to shipment cache billing rates calculated
        if (!empty($billingRateToAdd)) {
            app(ShipmentBillingCacheService::class)->updateShipmentCalculatedBillingRate(
                $record,
                $billingRateToAdd
            );
        }

        if (!empty($billingRateToUpdateTimeStamp)) {
            app(ShipmentBillingCacheService::class)->updateShipmentCalculatedBillingRateByBillingRate(
                $record,
                $billingRateToUpdateTimeStamp
            );
        }

        //deletes charges marked for deletion
        if (!empty($chargesToDelete)) {
            //delete charges in
            foreach ($chargesToDelete as $charge) $charge->delete();
        }
    }

    /**
     * @param array $chargesToAdd
     * @param array $chargeDocs
     * @param array $chargesDTOs
     * @return array
     */
    private function getCharges(array $chargesToAdd, array $chargeDocs, array $chargesDTOs): array
    {
        foreach ($chargesToAdd as $chargeToAdd) {
            $class = get_class($chargeToAdd->first());
            $chargeDocs[self::FULFILLMENT_CHARGES_TYPE[$class]] = $chargeDocs[self::FULFILLMENT_CHARGES_TYPE[$class]]->merge($chargeToAdd);
        }

        foreach ($chargeDocs as $key => $value) {
            $chargesDTOs[$key] = $value->map(function ($el) {
                return $el->getCharges();
            });
        }

        $charges = $this->formatCharges(
            $chargesDTOs[BillingRate::SHIPMENTS_BY_SHIPPING_LABEL],
            $chargesDTOs[BillingRate::SHIPMENTS_BY_PICKING_RATE_V2],
            $chargesDTOs[BillingRate::PACKAGING_RATE],
        );
        return array($chargeDocs, $charges);
    }

    private function addOrdersWithMultipleShipments($shipments, array $ordersBilledAlready): array
    {
        /** @var Shipment $shipment */
        foreach ($shipments as $shipment) {
            if ($shipment->order->hasMultipleShipments()) {
                $ordersBilledAlready[] = $shipment->order->id;
            }
        }

        return $ordersBilledAlready;
    }

    private function filterShipmentsByOrdersWithMultipleShipments(array $chargeDocs, array $orderIds): array
    {
        // only with picking so far
        $chargeDocs[self::FULFILLMENT_CHARGES_TYPE[PickingBillingRateShipmentCacheDocument::class]] = $chargeDocs[self::FULFILLMENT_CHARGES_TYPE[PickingBillingRateShipmentCacheDocument::class]]
            ->filter(function ($charges) use ($orderIds) {
                return !in_array($charges['order_id'], $orderIds);
            });

        $chargeDocs[self::FULFILLMENT_CHARGES_TYPE[ShippingLabelRateShipmentCacheDocument::class]] = $chargeDocs[self::FULFILLMENT_CHARGES_TYPE[ShippingLabelRateShipmentCacheDocument::class]]
            ->filter(function ($charges) use ($orderIds) {
                return !in_array($charges['order_id'], $orderIds);
            });

        return $chargeDocs;
    }
}
