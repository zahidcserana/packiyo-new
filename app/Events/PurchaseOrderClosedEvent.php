<?php

namespace App\Events;

use App\Interfaces\AutomatableEvent;
use App\Interfaces\BillableEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\PurchaseOrder;

class PurchaseOrderClosedEvent implements BillableEvent, AutomatableEvent
{
    use Dispatchable, SerializesModels;

    protected PurchaseOrder $purchaseOrder;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;
    }

    public function getOperation(): PurchaseOrder
    {
        return $this->purchaseOrder;
    }

    public static function getTitle(): String
    {
        return 'Purchase Order Closed';
    }
}
