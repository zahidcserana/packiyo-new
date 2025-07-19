<?php

namespace App\Components\Invoice\Strategies;

use App\Components\BillingRates\BillingRateInterface;
use App\Components\InvoiceExportComponent;
use App\Enums\InvoiceStatus;
use App\Exceptions\BillingException;
use App\Exceptions\BillingRateException;
use App\Exceptions\InvoiceGenerationException;
use App\Models\BillingBalance;
use App\Models\BillingRate;
use App\Models\Invoice;
use App\Models\User;
use App\Traits\InvoiceTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * @description Contains logic from legacy approach on how to generate invoice line items.
 */
class InvoiceLegacyStrategy implements InvoiceStrategyInterface
{
    use InvoiceTrait;

    protected array $billingRateComponents;
    protected array $ratesThatTrackBilledOperations;

    public function __construct(
        protected InvoiceExportComponent $invoiceExport,
        BillingRateInterface ...$billingRateComponents
    )
    {
        $this->billingRateComponents = [];
        $this->ratesThatTrackBilledOperations = [];

        foreach ($billingRateComponents as $billingRateComponent) {
            $this->billingRateComponents[$billingRateComponent::$rateType] = $billingRateComponent;

            if ($billingRateComponent->tracksBilledOperations()) {
                $this->ratesThatTrackBilledOperations[] = $billingRateComponent;
            }
        }
    }

    public function bill(Invoice $invoice, array $billableOperations = [], ?User $user = null): bool
    {
        Log::debug('Using Invoice Legacy strategy for invoice id: ' . $invoice->id);
        try {
            $this->billByRates($invoice);
        } catch (BillingException $e) {
            Log::warning("Error during Invoice id: {$invoice->id} generation: " . $e->getMessage());
            report($e);
            return false;
        }

        return true;
    }

    /**
     * @throws BillingException|InvoiceGenerationException
     */
    private function billByRates(Invoice $invoice): void
    {
        try {
            $rateCardsRel = $invoice->customer->rateCards();
            $billingRates = BillingRate::whereIn('rate_card_id', $rateCardsRel->pluck('rate_cards.id')->toArray())
                ->where('is_enabled', 1)
                ->where('type', '<>', 'ad_hoc')
                ->orderBy('settings->if_no_other_rate_applies')
                ->get();

            $this->invoiceExport->deleteGeneratedCsv($invoice);

            /** @var BillingRate $rate */
            foreach ($billingRates as $rate) {
                if (in_array($rate->type, BillingRate::DOC_DB_ONLY_RATES)) {
                    continue;
                }

                try {
                    $this->getRateComponent($rate)->calculate($rate, $invoice);
                } catch (Throwable $exception) {
                    $newException = new BillingRateException($rate, $exception);
                    Log::warning($newException->getMessage());

                    throw $newException;
                }
            }

            $this->invoiceItemsFromBalanceCharges($invoice);

            foreach ($this->ratesThatTrackBilledOperations as $billingRateComponent) {
                $billingRateComponent->resetBilledOperations();
            }

            $invoice->calculated_at = Carbon::now()->toDateTimeString();
            $invoice->amount = $invoice->invoiceLineItems->sum('total_charge');
        } catch (BillingRateException $e) {
            $newException = InvoiceGenerationException::createByBillingRateException($e, $invoice);
            Log::warning($newException->getMessage());
            $invoice->setInvoiceStatus(InvoiceStatus::FAILED_STATUS);

            throw $newException;
        } catch (Throwable $e) {
            $message = sprintf(
                "Invoice Generation occur: %s, on Invoice number: %s, for client: %s",
                $e->getMessage(),
                $invoice->invoice_number,
                $invoice->customer->contactInformation->name ?? ''
            );
            $newException = new InvoiceGenerationException($message, 500, $e);
            Log::warning($message);
            // any error that could occur
            $invoice->setInvoiceStatus(InvoiceStatus::FAILED_STATUS);

            throw $newException;
        }

        $invoice->save();
        $invoice->setInvoiceStatus(InvoiceStatus::DONE_STATUS);
    }

    /**
     * @throws BillingException
     * @todo This method is where everything is tied together with wire.
     */
    protected function invoiceItemsFromBalanceCharges(Invoice $invoice): void
    {
        Log::channel('billing')->info('[BillingRate] Start calculating invoice items from billing balance.');

        // TODO: Use this to filter charges in the balance.
        $from = Carbon::parse($invoice->period_start);
        $to = Carbon::parse($invoice->period_end);

        // For the different warehouses.
        $balances = BillingBalance::where([
            'threepl_id' => $invoice->customer->parent_id,
            'client_id' => $invoice->customer_id
        ])->get();

        if ($balances->isNotEmpty()) {
            foreach ($balances as $balance) {
                $charges = $balance->billingCharges()
                    ->whereDate('created_at', '>=', $from)
                    ->whereDate('created_at', '<=', $to)
                    ->get();

                foreach ($charges as $charge) {
                    if (!is_null($charge->billingRate)) {
                        // Just ad hoc charges - these will be redesigned.
                        $this->createInvoiceLineItemWithParams($charge->description, $invoice, $charge->billingRate, [
                            'shipment_id' => $charge->shipment_id,
                            // TODO: Fix this.
                            'fee' => $charge->amount / $charge->quantity
                        ], $charge->quantity, $charge->created_at);
                    } elseif (!is_null($charge->automation)) {
                        // Copilot-based billing charges.
                        $this->createInvoiceLineItemWithParams($charge->description, $invoice, null, [
                            'shipment_id' => $charge->shipment_id,
                            // TODO: Fix this.
                            'fee' => $charge->amount / $charge->quantity
                        ], $charge->quantity, $charge->created_at);
                    }
                }
            }
        }

        Log::channel('billing')->info('[BillingRate] End calculating invoice items from billing balance.');
    }


    /**
     * @throws BillingException
     */
    private function getRateComponent(BillingRate $rate): BillingRateInterface
    {
        if (!array_key_exists($rate->type, $this->billingRateComponents)) {
            throw new BillingException('The rate type "' . $rate->type . '" has no registered component.');
        }

        return $this->billingRateComponents[$rate->type];
    }
}
