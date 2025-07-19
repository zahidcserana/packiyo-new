<?php

namespace App\Events;

use App\Interfaces\ConditionalAutomatableEvent;
use App\Models\AutomationEventCondition;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class OrderAgedEvent implements ConditionalAutomatableEvent
{
    use Dispatchable, SerializesModels;

    protected AutomationEventCondition $condition;
    protected Order $order;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(AutomationEventCondition $condition, Order $order)
    {
        $this->condition = $condition;
        $this->order = $order;
    }

    public function getCondition(): AutomationEventCondition
    {
        return $this->condition;
    }

    public function getOperation(): Order
    {
        return $this->order;
    }

    public function runAutomationOnSelf(): void
    {
        $this->getCondition()->automation->run($this);
    }

    public static function getTitle(): String
    {
        return 'Order Aged';
    }
}
