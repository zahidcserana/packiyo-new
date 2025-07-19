<?php

namespace App\Components\Invoice\Helpers;

use App\Components\Invoice\DataTransferObjects\FulfillmentBillableOperationDto;
use App\Components\Invoice\DataTransferObjects\BillableOperationDto;
use App\Components\Invoice\DataTransferObjects\ReceivingBillableOperationDto;
use App\Components\Invoice\DataTransferObjects\StorageBillableOperationDto;
use App\Models\BillingRate;
use Illuminate\Support\Collection;

class InvoiceHelper
{
    public static function getBillableOperationDtoByBillingRates(Collection $billingRates): array
    {
        $billableOperations = [];
        $fulfillmentBillingRates = [];
        $storageBillingRates = [];
        $receivingBillingRates = [];

        // TODO: We really need to stop doing this and start doing proper OOP polymorphism.
        /** @var BillingRate $billingRate */
        foreach ($billingRates as $billingRate) {
            switch ($billingRate->getBillableOperationType()) {
                case BillableOperationDto::FULFILLMENT_TYPE:
                    $fulfillmentBillingRates[] = $billingRate;
                    break;
                case BillableOperationDto::RECEIVING_TYPE:
                    $receivingBillingRates[] = $billingRate;
                    break;
                case BillableOperationDto::STORAGE_TYPE:
                    $storageBillingRates[] = $billingRate;
                break;
            }
            // TODO: Add for other operations storage -- no, actually, just implement this polymorphically.
        }

        if(!empty($fulfillmentBillingRates)){
            $billableOperationFulfillmentDto = new FulfillmentBillableOperationDto;
            $billableOperationFulfillmentDto->setBillingRates($fulfillmentBillingRates);
            $billableOperations[] = $billableOperationFulfillmentDto;
        }

        if(!empty($storageBillingRates)){
            $billableOperationStorageDto = new StorageBillableOperationDto;
            $billableOperationStorageDto->setBillingRates($storageBillingRates);
            $billableOperations[] = $billableOperationStorageDto;
        }

        if(!empty($receivingBillingRates)){
            $billableOperationReceivingDto = new ReceivingBillableOperationDto;
            $billableOperationReceivingDto->setBillingRates($receivingBillingRates);
            $billableOperations[] = $billableOperationReceivingDto;
        }

        // TODO: Will add more when we start working on it - nope, will refactor to make polymorphic.
        return $billableOperations;
    }
}
