<?php

namespace App\Components\Invoice\Strategies;

use App\Components\Invoice\DataTransferObjects\BillableOperationDto;
use App\Jobs\Billing\InvoiceGenerationOnTheFlyJob;
use App\Models\CacheDocuments\InvoiceCacheDocument;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class InvoiceMongoStrategy implements InvoiceStrategyInterface
{
    public function bill(Invoice $invoice, array $billableOperations = [], ?User $user = null): bool
    {
        Log::debug('Using Invoice Mongo strategy: ' . $invoice->id);

        if (!empty($billableOperations)) {
            $billingRates = $this->getBillingRatesFromBillableOperations($billableOperations);
            $this->createInvoiceCacheDocument($invoice, $billingRates);
            InvoiceGenerationOnTheFlyJob::dispatch($invoice, $user);

            return true;
        }

        return false;
    }

    /**
     * @param array $billableOperations
     * @return array
     */
    private function getBillingRatesFromBillableOperations(array $billableOperations): array
    {
        $billingRates = [];

        /* @var BillableOperationDto $billableOperation */
        foreach ($billableOperations as $billableOperation) {
            collect($billableOperation->getBillingRates())->each(function ($element) use (&$billingRates) {
                $billingRates[] = $element->toArray();
            });
        }
        return $billingRates;
    }

    /**
     * @param Invoice $invoice
     * @param array $billingRates
     * @return void
     */
    private function createInvoiceCacheDocument(Invoice $invoice, array $billingRates): void
    {
        $invoiceCacheDocument = InvoiceCacheDocument::build($invoice, $billingRates);
        $invoiceCacheDocument->save();
    }
}
