<?php

namespace App\Listeners;

use App\Components\BillableOperationService;
use App\Events\OccupiedLocationsCalculatedEvent;
use App\Events\OrderShippedEvent;
use App\Events\PurchaseOrderClosedEvent;
use App\Interfaces\BillableEvent;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class BillingListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        private readonly BillableOperationService $billableOperationService,
    )
    {
    }

    public function handle(BillableEvent $event): void
    {
        try {
        switch ($event) {
            case $event instanceof OrderShippedEvent:
                $this->handleOrderShippedEvent($event);
                break;

            case $event instanceof OccupiedLocationsCalculatedEvent:
                $this->handleOccupiedLocationsCalculatedEvent($event);
                break;

            case $event instanceof PurchaseOrderClosedEvent:
                $this->handlePurchaseOrderClosedEvent($event);
                break;

                default:
                    Log::warning('Event not handle '. get_class($event));
                    throw new InvalidArgumentException('Unexpected event type');
            }
        } catch (Throwable $e) {
            $this->warnOfError($e);
        }
    }

    private function handleOrderShippedEvent(OrderShippedEvent $event): void
    {
        $this->billableOperationService->handleShipmentsOperation($event->getShipments(), true);
    }

    private function handlePurchaseOrderClosedEvent(PurchaseOrderClosedEvent $event): void
    {
        $this->billableOperationService->handleReceivingOperation($event->getOperation());
    }

    private function handleOccupiedLocationsCalculatedEvent($event): void
    {
        $this->billableOperationService->handleStorageOperation($event->client, $event->warehouse, $event->calendarDate);
    }

    protected function warnOfError(Throwable $e) {
        Log::warning("Billing error in {$e->getFile()}:{$e->getLine()}: " . $e->getMessage());
        Log::warning("Stack trace:\n" . $e->getTraceAsString());
    }
}
