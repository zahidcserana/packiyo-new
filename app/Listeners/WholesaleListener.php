<?php

namespace App\Listeners;

use App\Components\WholesaleComponent;
use App\Events\OrderShippedEvent;
use App\Features\WholesaleEDI;
use App\Models\EDI\Providers\CrstlEDIProvider;

class WholesaleListener
{
    public function __construct(protected WholesaleComponent $wholesaleComponent)
    {
    }

    public function handle(OrderShippedEvent $event)
    {
        $order = $event->getOperation();
        $customer = $order->customer;
        $shipments = $event->getShipments();

        if ($customer->is3plChild()) {
            $customer = $customer->parent;
        }

        if ($customer->hasFeature(WholesaleEDI::class) && $order->is_wholesale) {
            $ediProvider = $this->wholesaleComponent->getProviderForOrder($order);

            if ($ediProvider) {
                $this->wholesaleComponent->createPackingLabels($ediProvider, $order, 0, ...$shipments);
            }
        }
    }
}
