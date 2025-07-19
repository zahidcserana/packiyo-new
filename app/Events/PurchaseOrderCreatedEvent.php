<?php

namespace App\Events;

use App\Interfaces\AutomatableEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\PurchaseOrder;

class PurchaseOrderCreatedEvent implements AutomatableEvent
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
        return 'Purchase Order Created';
    }
}
