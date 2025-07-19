<?php

namespace App\Events;

use App\Interfaces\AutomatableEvent;
use App\Interfaces\BillableEvent;
use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Shipment;

class OrderShippedEvent implements BillableEvent, AutomatableEvent
{
    use Dispatchable, SerializesModels;

    protected Order $order;
    protected array $shipments;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Order $order, Shipment ...$shipments)
    {
        $this->order = $order;
        $this->shipments = $shipments;
    }

    public function getOperation(): Order
    {
        return $this->order;
    }

    public function getShipments(): array
    {
        return $this->shipments;
    }

    public static function getTitle(): String
    {
        return 'Order Shipped';
    }
}
