<?php

namespace App\Components\Invoice;

use App\Components\BillableOperationService;
use App\Components\BillingRates\StorageByLocationRate\MongoDbConnectionTester;
use App\Components\InventoryLogComponent;
use App\Components\Invoice\DataTransferObjects\BillableOperationDto;
use App\Components\Invoice\Helpers\InvoiceHelper;
use App\Components\Invoice\Strategies\InvoiceLegacyStrategy;
use App\Components\Invoice\Strategies\InvoiceMongoStrategy;
use App\Components\Invoice\Strategies\InvoiceStrategyInterface;
use App\Enums\InvoiceStatus;
use App\Exceptions\BillingRateException;
use App\Features\Wallet;
use App\Models\BillingRate;
use App\Models\CacheDocuments\PackagingRateShipmentCacheDocument;
use App\Models\CacheDocuments\PickingBillingRateShipmentCacheDocument;
use App\Models\CacheDocuments\PurchaseOrderCacheDocument;
use App\Models\CacheDocuments\PurchaseOrderChargeCacheDocument;
use App\Models\CacheDocuments\ShipmentCacheDocument;
use App\Models\CacheDocuments\ShippingLabelRateShipmentCacheDocument;
use App\Models\CacheDocuments\StorageByLocationChargeCacheDocument;
use App\Models\CacheDocuments\WarehouseOccupiedLocationTypesCacheDocument;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class InvoiceProcessor
{
    const CHUNK_SIZE = 100;

    public function __construct(
        private readonly InvoiceLegacyStrategy $legacyStrategy,
        private readonly InvoiceMongoStrategy $mongoStrategy,
        private readonly MongoDbConnectionTester $connectionTester,
        private readonly BillableOperationService $billableOperationService,
        private readonly InventoryLogComponent $inventoryLogComponent
    )
    {
    }

    /**
     * @throws Exception
     */
    public function bill(Invoice $invoice, ?User $user = null): void
    {
        Log::debug('Start generating Invoice items for Invoice id: ' . $invoice->id);
        $invoice->setInvoiceStatus(InvoiceStatus::CALCULATING_STATUS);
        $billingRates = $this->getInvoiceBillingRates($invoice);

        if ($billingRates->isEmpty()) {
            // Even when they are no billing rates, legacy strategy runs other task that automations required.
            Log::debug("No billing rates available assign to invoice id: {$invoice->id}, nothing to charge");
            $this->getLegacyStrategy()->bill($invoice);
            return;
        }

        $billableOperations = InvoiceHelper::getBillableOperationDtoByBillingRates($billingRates);
        $strategy = $this->getStrategyByThreePl($invoice->customer->parent);
        //Maybe move this to a different file ?
        $strategy = $this->validateStrategy($strategy, $billableOperations, $invoice);
        $result = $strategy->bill($invoice, $billableOperations); // use true or false

        if (!$result && $strategy instanceof InvoiceMongoStrategy) {
            Log::debug("Using legacy strategy for Invoice id: " . $invoice->id);
            $this->getLegacyStrategy()->bill($invoice);
        }

        Log::debug("End generating Invoice items " . $invoice->id);
    }

    public function getStrategyByThreePl(Customer $customer3pl): InvoiceStrategyInterface
    {
        if ($customer3pl->hasFeature(Wallet::class) && $this->connectionTester->testConnection()) { //similar to Mateus
            return $this->mongoStrategy;
        }

        return $this->getLegacyStrategy();
    }

    private function getLegacyStrategy(): InvoiceStrategyInterface
    {
        return $this->legacyStrategy;
    }


    private function validateBillableOperationsByAmountOfDocuments(array $billableOperations, Invoice $invoice): array
    {
        $result = true;
        $billableOperationWithError = [];
        $periodStart = Carbon::parse($invoice->period_start)->startOfDay();
        $periodEnd = Carbon::parse($invoice->period_end)->endOfDay();
        $customer = $invoice->customer;

        /* @var BillableOperationDto $billableOperation */
        foreach ($billableOperations as $billableOperation) {
            if ($billableOperation->isFulfillmentOperation()) {
                Shipment::whereHas('order', static function (Builder $query) use ($customer) {
                    $query->where('customer_id', $customer->id);
                })->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->whereNull('voided_at')
                    ->chunkById(self::CHUNK_SIZE, function ($shipments) use (&$billableOperationWithError, &$result, $invoice) {
                        $result = $this->validateShipmentCacheDocumentsAmount($shipments);
                        if (!$result) {
                            Log::channel('billing')->debug(
                                sprintf(
                                    "[Invoice Id: %s] Shipments amounts not matching cache documents",
                                    $invoice->id
                                ));
                            $billableOperationWithError[] = BillableOperationDto::FULFILLMENT_TYPE;
                            return false;
                        }
                    });
            } elseif ($billableOperation->isReceivingOperation()) {
                PurchaseOrder::where(['customer_id' => $customer->id])
                    ->whereBetween('closed_at', [$periodStart, $periodEnd])
                    ->chunkById(self::CHUNK_SIZE, function ($purchaseOrders) use (&$billableOperationWithError, &$result, $invoice) {
                        $result = $this->validatePurchaseOrderAmount($purchaseOrders);
                        if (!$result) {
                            Log::channel('billing')->debug(
                                sprintf(
                                    "[Invoice Id:%s] Purchase order amounts not matching cache documents",
                                    $invoice->id
                                ));
                            $billableOperationWithError[] = BillableOperationDto::RECEIVING_TYPE;
                            return false;
                        }
                    });

            } elseif ($billableOperation->isStorageOperation()) {
                $customer = $invoice->customer; //ask to javi, do we use the client or the 3pl
                $warehouseId = $customer->parent->warehouses->pluck('id')->toArray();

                $periodCalculations = WarehouseOccupiedLocationTypesCacheDocument::query()
                    ->whereBetween('calendar_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                    ->where('customer_id', $customer->id)
                    ->whereIn('warehouse_id', $warehouseId)
                    ->count();

                $periodCount = ($periodStart->diffInDays($periodEnd) + 1) * count($warehouseId);
                $result = $periodCalculations == $periodCount;
                if (!$result) {
                    Log::channel('billing')->debug(
                        sprintf(
                            "[Invoice Id: %s] Storage period not matching cache documents",
                            $invoice->id
                        ));
                    $billableOperationWithError[] = BillableOperationDto::STORAGE_TYPE;
                }
            }
        }

        //if any of the operation is missing document, fail this
        return [$result, $billableOperationWithError];
    }

    public function generateMissingBillableOperationDocuments(array $billableOperations, Invoice $invoice): void
    {
        $customer = $invoice->customer;
        $periodStart = Carbon::parse($invoice->period_start)->startOfDay();
        $periodEnd = Carbon::parse($invoice->period_end)->endOfDay();

        foreach ($billableOperations as $billableOperation) {
            $chargeDocs = [];
            if ($billableOperation == BillableOperationDto::FULFILLMENT_TYPE) {
                $missingShipments = collect();

                Shipment::whereHas('order', static function (Builder $query) use ($customer) {
                    $query->where('customer_id', $customer->id);
                })->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->whereNull('voided_at')
                    ->chunkById(self::CHUNK_SIZE, function ($shipments) use (&$chargeDocs, &$missingShipments) {
                        $shipmentIdsChunk = $shipments->pluck('id')->toArray();
                        $docsShipmentsIds = $this->getShipmentDocumentIds($shipmentIdsChunk);
                        $missingShipmentIdsOnChunk = array_diff($shipmentIdsChunk, $docsShipmentsIds);

                        if (!empty($missingShipmentIdsOnChunk)) {
                            //does the operation contains charges
                            $chargeDocs[BillingRate::SHIPMENTS_BY_SHIPPING_LABEL] = ShippingLabelRateShipmentCacheDocument::whereIn('shipment_id', $missingShipmentIdsOnChunk)->get();
                            $chargeDocs[BillingRate::SHIPMENTS_BY_PICKING_RATE_V2] = PickingBillingRateShipmentCacheDocument::whereIn('shipment_id', $missingShipmentIdsOnChunk)->get();
                            $chargeDocs[BillingRate::PACKAGING_RATE] = PackagingRateShipmentCacheDocument::whereIn('shipment_id', $missingShipmentIdsOnChunk)->get();

                            $missingShipments = $missingShipments->merge($shipments->whereIn('id', $missingShipmentIdsOnChunk));
                        }
                    });

                $this->billableOperationService->handleShipmentsOperation($missingShipments->all());
            } elseif ($billableOperation == BillableOperationDto::RECEIVING_TYPE) {
                $missingPurchaseOrders = collect();
                PurchaseOrder::where(['customer_id' => $customer->id])
                    ->whereBetween('closed_at', [$periodStart, $periodEnd])
                    ->chunkById(self::CHUNK_SIZE, function ($purchaseOrders) use (&$chargeDocs, &$missingPurchaseOrders) {
                        $purchaseOrdersIds = $purchaseOrders->pluck('id')->toArray();
                        $docsPurchaseOrderIds = PurchaseOrderCacheDocument::whereIn('purchase_order_id', $purchaseOrdersIds)->pluck('purchase_order_id')->toArray();
                        $missingPurchaseOrderIdsOnChunk = array_diff($purchaseOrdersIds, $docsPurchaseOrderIds);
                        //does the operation contains charges
                        if (!empty($missingPurchaseOrderIdsOnChunk)) {
                            $chargeDocs[BillingRate::PURCHASE_ORDER] = PurchaseOrderChargeCacheDocument::whereIn('purchase_order_id', $missingPurchaseOrderIdsOnChunk)->get();
                            $missingPurchaseOrders = $missingPurchaseOrders->merge($purchaseOrders->whereIn('id', $missingPurchaseOrderIdsOnChunk));
                        }
                    });

                foreach ($missingPurchaseOrders as $missingPurchaseOrder) {
                    $this->billableOperationService->handleReceivingOperation($missingPurchaseOrder);
                }
            } elseif ($billableOperation == BillableOperationDto::STORAGE_TYPE) {
                $warehouseIds = $customer->parent->warehouses->pluck('id')->toArray();
                $allDates = [];

                $warehousePeriodStart = $periodStart->subDay();
                $period = CarbonPeriod::create($warehousePeriodStart, $periodEnd);
                $missingData = [];
                foreach ($period as $date) {
                    $allDates[] = $date;
                }

                foreach ($warehouseIds as $warehouseId) {
                    $existingDates = WarehouseOccupiedLocationTypesCacheDocument::query()
                        ->whereBetween('calendar_date', [$warehousePeriodStart->toDateString(), $periodEnd->toDateString()])
                        ->where('customer_id', $customer->id)
                        ->where('warehouse_id', $warehouseId)
                        ->pluck('calendar_date')
                        ->toArray();

                    $missingDates = array_diff($allDates, $existingDates);
                    if (!empty($missingDates)) {
                        $missingData[$warehouseId] = $missingDates;
                    }
                }

                foreach ($missingData as $warehouseId => $missingDates) {
                    $warehouse = Warehouse::find($warehouseId);
                    foreach ($missingDates as $missingDate) {
                        $calendarDate = Carbon::parse($missingDate);
                        $this->inventoryLogComponent->calculateLocationsOccupiedByCustomer(
                            $customer,
                            $warehouse,
                            \Illuminate\Support\Carbon::parse($missingDate)->addDay(),
                            dispatchEvent: false
                        );
                        $this->billableOperationService->handleStorageOperation(
                            $customer,
                            $warehouse,
                            $calendarDate
                        );
                    }
                }
                // no need to delete charges, when creating new docs will generate new ones.
            }
            // delete charges if any to not affect things when generating invoice
            $this->deleteChargesDocuments($chargeDocs);
        }
    }

    /**
     * @param Invoice $invoice
     * @return Collection
     */
    private function getInvoiceBillingRates(Invoice $invoice): Collection
    {
        return BillingRate::whereIn('rate_card_id', $invoice->ratecards->pluck('id')->toArray())
            ->where('is_enabled', 1)
            ->where('type', '<>', 'ad_hoc')
            ->orderBy('settings->if_no_other_rate_applies')
            ->get();
    }

    /**
     * @param InvoiceStrategyInterface $strategy
     * @param array $billableOperations
     * @param Invoice $invoice
     * @return InvoiceMongoStrategy|InvoiceStrategyInterface
     */
    private function validateStrategy(InvoiceStrategyInterface $strategy, array $billableOperations, Invoice $invoice): InvoiceMongoStrategy|InvoiceStrategyInterface
    {
        if ($strategy instanceof InvoiceMongoStrategy) {
            //validates amount of cache documents match with billable operation
            [$documentsAreAvailable, $billableOperationsWithError] = $this->validateBillableOperationsByAmountOfDocuments($billableOperations, $invoice);

            if (!$documentsAreAvailable) {
                try {
                    Log::channel('billing')->debug(sprintf(
                        "[Invoice Processor][Invoice id: %s] Missing billable operation documents, generating missing documents",
                        $invoice->id));
                    $this->generateMissingBillableOperationDocuments($billableOperationsWithError, $invoice);
                } catch (Exception $exception) {
                    Log::channel('billing')->warning(
                        sprintf(
                            "[Invoice Processor][Invoice id: %s] Using legacy strategy, for error: %s during generation of billable operation documents",
                            $invoice->id,
                            $exception->getMessage()
                        ));
                    return $this->getLegacyStrategy();
                }
            }

            //validates cache documents billing rates match with invoice
            [$documentsAreAvailable, $billableOperationsWithError] = $this->validateBillableOperationsByBillingRates($billableOperations, $invoice);
            if (!$documentsAreAvailable) {
                try {
                    Log::channel('billing')->debug(
                        sprintf(
                            "[Invoice Processor][Invoice id: %s] Billable operation documents with invalid billing rates, generating new documents",
                            $invoice->id)
                    );
                    $this->generateInvalidBillableOperationDocuments($billableOperationsWithError, $invoice);
                } catch (Exception $exception) {
                    Log::channel('billing')->warning(
                        sprintf(
                            "[Invoice Processor][Invoice id: %s] Using legacy strategy, for error : %s during generation of billable operation documents",
                            $invoice->id,
                            $exception->getMessage()
                        )
                    );
                    return $this->getLegacyStrategy();
                }
            }
        }

        return $strategy;
    }

    /**
     * @param Collection $shipments
     * @return bool
     */
    public function validateShipmentCacheDocumentsAmount(Collection $shipments): bool
    {
        $shipmentIdsChunk = $shipments->pluck('id');
        $shipmentDocsCount = ShipmentCacheDocument::whereIn('shipments.id', $shipmentIdsChunk)->pluck('shipments.id')->count();
        return count($shipmentIdsChunk) == $shipmentDocsCount;
    }

    /**
     * @param $purchaseOrders
     * @return bool
     */
    function validatePurchaseOrderAmount($purchaseOrders): bool
    {
        $purchaseOrdersIdsChunk = $purchaseOrders->pluck('id');
        $purchaseOrderDocsCount = PurchaseOrderCacheDocument::whereIn('purchase_order_id', $purchaseOrdersIdsChunk)->count();
        return count($purchaseOrdersIdsChunk) == $purchaseOrderDocsCount;
    }

    private function validateBillableOperationsByBillingRates(array $billableOperations, Invoice $invoice): array
    {
        $customer = $invoice->customer;
        $periodStart = Carbon::parse($invoice->period_start)->startOfDay();
        $periodEnd = Carbon::parse($invoice->period_end)->endOfDay();
        $result = true;
        $errors = [];

        /** @var BillableOperationDto $billableOperation */
        foreach ($billableOperations as $billableOperation) {
            $billingRatesId = collect($billableOperation->getBillingRates())->pluck('id')->toArray();
            if ($billableOperation->isFulfillmentOperation()) {

                Shipment::whereHas('order', static function (Builder $query) use ($customer) {
                    $query->where('customer_id', $customer->id);
                })->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->whereNull('voided_at')
                    ->chunkById(self::CHUNK_SIZE, function ($shipments) use ($billingRatesId, &$errors) {

                        $shipmentIdsChunk = $shipments->pluck('id');
                        $docs = ShipmentCacheDocument::whereIn('shipments.id', $shipmentIdsChunk)->select('id', 'calculated_billing_rates')->get();
                        $errors = $this->getBillingRatesErrorsFromDocs($docs, $billingRatesId, BillableOperationDto::FULFILLMENT_TYPE);
                    });

                if (!empty($errors)) {
                    $result = false;
                }

            } elseif ($billableOperation->isReceivingOperation()) {

                PurchaseOrder::where(['customer_id' => $customer->id])
                    ->whereBetween('closed_at', [$periodStart, $periodEnd])
                    ->chunkById(self::CHUNK_SIZE, function ($purchaseOrders) use ($billingRatesId, &$errors) {

                        $purchaseOrderIdsChunk = $purchaseOrders->pluck('id');
                        $docs = PurchaseOrderCacheDocument::whereIn('purchase_order_id', $purchaseOrderIdsChunk)->select('id', 'calculated_billing_rates')->get();
                        $errors = $this->getBillingRatesErrorsFromDocs($docs, $billingRatesId, BillableOperationDto::RECEIVING_TYPE);
                    });

                if (!empty($errors)) {
                    $result = false;
                }
            } elseif ($billableOperation->isStorageOperation()) {
                $warehouseIds = $customer->parent->warehouses->pluck('id')->toArray();

                $warehousePeriodStart = $periodStart->subDay();
                WarehouseOccupiedLocationTypesCacheDocument::where('customer_id', $customer->id)
                    ->whereIn('warehouse_id', $warehouseIds)
                    ->whereBetween('calendar_date', [$warehousePeriodStart->toDateString(), $periodEnd->toDateString()])
                    ->chunkById(self::CHUNK_SIZE, function ($warehouseOccupiedLocationTypesCacheDocument) use ($billingRatesId, &$errors) {

                        $errors = $this->getBillingRatesErrorsFromDocs($warehouseOccupiedLocationTypesCacheDocument, $billingRatesId, BillableOperationDto::STORAGE_TYPE);
                    });

                if (!empty($errors)) {
                    $result = false;
                }
            }
        }
        return [$result, $errors];
    }

    /**
     * @throws BillingRateException
     */
    private function generateInvalidBillableOperationDocuments(array $billableOperationsWithError, Invoice $invoice): void
    {
        $periodStart = Carbon::parse($invoice->period_start)->startOfDay();
        $periodEnd = Carbon::parse($invoice->period_end)->endOfDay();

        foreach ($billableOperationsWithError as $key => $documents) {
            $chargeDocs = [];
            if ($key == BillableOperationDto::FULFILLMENT_TYPE) {
                $shipmentsToGenerate = collect();
                ShipmentCacheDocument::whereIn('_id', $documents)
                    ->chunkById(self::CHUNK_SIZE, function ($docs) use (&$shipmentsToGenerate, &$chargeDocs, $periodStart, $periodEnd) {
                        /** @var ShipmentCacheDocument $doc */
                        foreach ($docs as $doc) {
                            $orderId = $doc->getOrder()['id'];
                            $shipments = Shipment::where('order_id', $orderId)
                                ->whereBetween('created_at', [$periodStart, $periodEnd])
                                ->whereNull('voided_at') //shipments could be created before or after invoice period
                                ->get();
                            //does the operation contains charges
                            $chargeDocs = $this->assignChargesForFulfillmentOperation($chargeDocs, $shipments);
                            $shipmentsToGenerate = $shipmentsToGenerate->merge($shipments);
                        }
                    });

                if (!$shipmentsToGenerate->isEmpty()) {
                    ShipmentCacheDocument::whereIn('_id', $documents)->delete();
                    $this->deleteChargesDocuments($chargeDocs);
                    Log::channel('billing')->debug(
                        sprintf(
                            "[InvoiceProcessor][Invoice Id: %s] Generate cache documents for shipments, invalids by billable operation",
                            $invoice->id
                        )
                    );
                    $this->billableOperationService->handleShipmentsOperation($shipmentsToGenerate->all());
                }
            } elseif ($key == BillableOperationDto::RECEIVING_TYPE) {
                $purchaseOrderToGenerate = collect();
                PurchaseOrderCacheDocument::whereIn('_id', $documents)
                    ->chunkById(self::CHUNK_SIZE, function ($docs) use (&$purchaseOrderToGenerate, &$chargeDocs) {
                        $purchaseOrdersId = $docs->pluck('purchase_order_id')->toArray();

                        $chargeDocs = $this->assignChargesForReceiving($chargeDocs, $purchaseOrdersId);
                        $purchaseOrderToGenerate = $purchaseOrderToGenerate->merge(PurchaseOrder::whereIn('id', $purchaseOrdersId)->get());
                    });

                if (!$purchaseOrderToGenerate->isEmpty()) {
                    PurchaseOrderCacheDocument::whereIn('_id', $documents)->delete();
                    $this->deleteChargesDocuments($chargeDocs);
                    Log::channel('billing')->debug(
                        sprintf(
                            "[InvoiceProcessor][Invoice Id: %s]  Generate cache documents for purchase orders, invalids by billable operation",
                            $invoice->id
                        ));
                    foreach ($purchaseOrderToGenerate as $purchaseOrder) {
                        $this->billableOperationService->handleReceivingOperation($purchaseOrder);
                    }
                }
            } elseif ($key == BillableOperationDto::STORAGE_TYPE) {
                $customer = $invoice->customer;
                $warehouseIds = $customer->parent->warehouses->pluck('id')->toArray();
                $allDates = [];
                $period = CarbonPeriod::create($periodStart, $periodEnd);
                $missingData = [];
                foreach ($period as $date) {
                    $allDates[] = $date;
                }

                foreach ($warehouseIds as $warehouseId) {
                    $existingDates = WarehouseOccupiedLocationTypesCacheDocument::query()
                        ->whereIn('_id', $documents)
                        ->where('warehouse_id', $warehouseId)
                        ->pluck('calendar_date')
                        ->toArray();

                    if (empty($existingDates)) {
                        continue;
                    }
                    $missingData[$warehouseId] = $existingDates;
                }

                foreach ($missingData as $warehouseId => $missingDates) {
                    $warehouse = Warehouse::find($warehouseId);
                    foreach ($missingDates as $missingDate) {
                        $calendarDate = Carbon::parse($missingDate);
                        $this->inventoryLogComponent->calculateLocationsOccupiedByCustomer(
                            $customer,
                            $warehouse,
                            \Illuminate\Support\Carbon::parse($missingDate)->addDay(),
                            dispatchEvent: false
                        );
                        $this->billableOperationService->handleStorageOperation(
                            $customer,
                            $warehouse,
                            $calendarDate
                        );
                    }
                }
                // no need to delete charges, when creating new docs will generate new ones.
            }
        }
    }

    /**
     * @param Collection $docs
     * @param array $billingRatesId
     * @param string $billableOperationType
     * @return array
     */
    function getBillingRatesErrorsFromDocs(Collection $docs, array $billingRatesId, string $billableOperationType): array
    {
        $errors = [];
        foreach ($docs as $doc) {
            $calculatedBillingRatesId = collect($doc->calculated_billing_rates)->map(function ($rate) {
                return $rate['billing_rate_id'];
            })->toArray();
            $result = array_diff($calculatedBillingRatesId, $billingRatesId);
            if (!empty($result)) {
                $errors[$billableOperationType][] = $doc->id;
            }
        }
        return $errors;
    }

    /**
     * @param array $chargeDocs
     * @return void
     */
    private function deleteChargesDocuments(array $chargeDocs): void
    {
        foreach ($chargeDocs as $chargeDoc) {
            $chargeDoc->each->delete();
        }
    }

    /**
     * @param mixed $shipmentIdsChunk
     * @return array
     */
    function getShipmentDocumentIds(mixed $shipmentIdsChunk): array
    {
        $docsShipments = ShipmentCacheDocument::whereIn('shipments.id', $shipmentIdsChunk)->select('shipments')->get();
        $docsShipmentsIds = $docsShipments->map(function ($doc) {
            return collect($doc->shipments)->pluck('id')->toArray();
        })->toArray();
        return array_merge(...$docsShipmentsIds);
    }

    /**
     * @param array $chargeDocs
     * @param Collection $shipments
     * @return array
     */
    function assignChargesForFulfillmentOperation(array $chargeDocs, Collection $shipments): array
    {
        $chargeDocs[BillingRate::SHIPMENTS_BY_SHIPPING_LABEL] = isset($chargeDocs[BillingRate::SHIPMENTS_BY_SHIPPING_LABEL])
            ? $chargeDocs[BillingRate::SHIPMENTS_BY_SHIPPING_LABEL]->merge(
                ShippingLabelRateShipmentCacheDocument::whereIn('shipment_id', $shipments->pluck('id')->toArray())->get()
            )
            : ShippingLabelRateShipmentCacheDocument::whereIn('shipment_id', $shipments->pluck('id')->toArray())->get();

        $chargeDocs[BillingRate::SHIPMENTS_BY_PICKING_RATE_V2] = isset($chargeDocs[BillingRate::SHIPMENTS_BY_PICKING_RATE_V2])
            ? $chargeDocs[BillingRate::SHIPMENTS_BY_PICKING_RATE_V2]->merge(
                PickingBillingRateShipmentCacheDocument::whereIn('shipment_id', $shipments->pluck('id')->toArray())->get()
            )
            : PickingBillingRateShipmentCacheDocument::whereIn('shipment_id', $shipments->pluck('id')->toArray())->get();

        $chargeDocs[BillingRate::PACKAGING_RATE] = isset($chargeDocs[BillingRate::PACKAGING_RATE])
            ? $chargeDocs[BillingRate::PACKAGING_RATE]->merge(
                PackagingRateShipmentCacheDocument::whereIn('shipment_id', $shipments->pluck('id')->toArray())->get()
            )
            : PackagingRateShipmentCacheDocument::whereIn('shipment_id', $shipments->pluck('id')->toArray())->get();
        return $chargeDocs;
    }

    /**
     * @param array $chargeDocs
     * @param array $purchaseOrdersId
     * @return array
     */
    function assignChargesForReceiving(array $chargeDocs, array $purchaseOrdersId): array
    {
        $chargeDocs[BillingRate::PURCHASE_ORDER] = isset($chargeDocs[BillingRate::PURCHASE_ORDER])
            ? $chargeDocs[BillingRate::PURCHASE_ORDER]->merge(
                PurchaseOrderChargeCacheDocument::whereIn('purchase_order_id', $purchaseOrdersId)->get()
            )
            : PurchaseOrderChargeCacheDocument::whereIn('purchase_order_id', $purchaseOrdersId)->get();

        return $chargeDocs;
    }
}
