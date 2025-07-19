<?php

namespace App\Components\Invoice;

use App\Components\Invoice\DataTransferObjects\BillableOperationDto;
use App\Components\Invoice\Facades\MongoInvoiceReceivingFacade;
use App\Components\Invoice\Facades\MongoInvoiceShipmentFacade;
use App\Components\Invoice\Facades\MongoInvoiceStorageFacade;
use App\Components\Invoice\Helpers\InvoiceHelper;
use App\Enums\InvoiceStatus;
use App\Jobs\Billing\InvoiceGenerationSuccessJob;
use App\Jobs\Billing\InvoiceSummaryJob;
use App\Models\BillingRate;
use App\Models\CacheDocuments\InvoiceCacheDocument;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class MongoInvoiceGenerator
{
    public MongoInvoiceShipmentFacade $invoiceShipmentFacade;
    public MongoInvoiceReceivingFacade $invoiceReceivingFacade;
    public MongoInvoiceStorageFacade $invoiceStorageFacade;

    public function __construct(
        MongoInvoiceShipmentFacade $invoiceShipmentFacade,
        MongoInvoiceReceivingFacade $invoiceReceivingFacade,
        MongoInvoiceStorageFacade $invoiceStorageFacade
    )
    {
        $this->invoiceShipmentFacade = $invoiceShipmentFacade;
        $this->invoiceReceivingFacade = $invoiceReceivingFacade;
        $this->invoiceStorageFacade = $invoiceStorageFacade;
    }

    public function generate(Invoice $invoice, ?User $user = null): void
    {
        $invoice->setInvoiceStatus(InvoiceStatus::PENDING_STATUS);
        $invoice->save();
        $invoiceCacheDocument = InvoiceCacheDocument::whereId($invoice->id)->first();

        if (empty($invoiceCacheDocument)) {
            $invoice->setInvoiceStatus(InvoiceStatus::FAILED_STATUS);
            $invoice->save();
            Log::warning(sprintf('[Mongo Invoice Generation] Missing Invoice cache for invoice: %s', $invoice->id));
            return;
        }

        $billableOperations = $this->getBillableOperations($invoiceCacheDocument);
        $containsShipmentOperations = false;
        $containsReceivingOperations = false;
        $containsStorageOperations = false;
        $shipmentProcessResult = false;
        $receivingProcessResult = false;
        $storageProcessResult = false;

        // TODO: The DTOs are not for billable operations, they seem to be the rates.
        /* @var BillableOperationDto $billableOperation */
        foreach ($billableOperations as $billableOperation) {
            if ($billableOperation->isFulfillmentOperation()) {
                $containsShipmentOperations = true;
                $shipmentProcessResult = $this->invoiceShipmentFacade->generateInvoiceItemForShipments(
                    $invoiceCacheDocument,
                    $billableOperation->getBillingRates()
                );
            } elseif ($billableOperation->isReceivingOperation()) {
                $containsReceivingOperations = true;
                $receivingProcessResult = $this->invoiceReceivingFacade->generateInvoiceItemForPurchaseOver(
                    $invoiceCacheDocument,
                    $billableOperation->getBillingRates()
                );
            } elseif ($billableOperation->isStorageOperation()){
                // TODO: No other operation at this point.
                $containsStorageOperations = true;
                $storageProcessResult = $this->invoiceStorageFacade->generateInvoiceItemForStorage(
                    $invoiceCacheDocument,
                    $billableOperation->getBillingRates()
                );
            }
        }

        list($shipmentProcess, $receivingProcess, $storageProcess) = $this->confirmOperationSuccess(
            $containsShipmentOperations,
            $shipmentProcessResult,
            $containsReceivingOperations,
            $receivingProcessResult,
            $containsStorageOperations,
            $storageProcessResult
        );

        if ($shipmentProcess && $receivingProcess && $storageProcess) {
            InvoiceGenerationSuccessJob::dispatch($invoice, $user);
            InvoiceSummaryJob::dispatch($invoice);
        } else {
            $invoice->setInvoiceStatus(InvoiceStatus::FAILED_STATUS);
            $invoice->save();
            Log::warning('[Mongo Invoice Generation] No billable operations cache document found');
        }
    }

    private function getBillableOperations(InvoiceCacheDocument $invoiceCacheDocument): array
    {
        $billingRateCollection = [];

        foreach ($invoiceCacheDocument->getBillingRates() as $billingRate) {
            $rate = BillingRate::make($billingRate);
            $rate->id = $billingRate['id'];
            $rate->updated_at = $billingRate['updated_at'];
            $billingRateCollection[] = $rate;
        }

        return InvoiceHelper::getBillableOperationDtoByBillingRates(collect($billingRateCollection));
    }

    /**
     * @description Returns if all operations where successful, if one billable operation doesn't contain billing
     * rates skip but pass as successfully.
     * @param bool $containsShipmentOperations
     * @param bool $shipmentProcessResult
     * @param bool $containsReceivingOperations
     * @param bool $receivingProcessResult
     * @param bool $containsStorageOperations
     * @param bool $storageProcessResult
     * @return bool[]
     */
    private function confirmOperationSuccess(
        bool $containsShipmentOperations,
        bool $shipmentProcessResult,
        bool $containsReceivingOperations,
        bool $receivingProcessResult,
        bool $containsStorageOperations,
        bool $storageProcessResult,
    ): array
    {
        if ($containsShipmentOperations) {
            $shipmentProcess = $containsShipmentOperations && $shipmentProcessResult;
        } else {
            $shipmentProcess = !$containsShipmentOperations; // no process run, so skip
        }
        if ($containsReceivingOperations) {
            $receivingProcess = $containsReceivingOperations && $receivingProcessResult;
        } else {
            $receivingProcess = !$containsReceivingOperations; // no process run, so skip
        }
        if ($containsStorageOperations) {
            $storageProcess = $containsStorageOperations && $storageProcessResult;
        } else {
            $storageProcess = !$storageProcessResult;
        }
        return array($shipmentProcess, $receivingProcess, $storageProcess);
    }
}
