<?php

namespace App\Events;

use App\Enums\Source;
use App\Interfaces\AutomatableEvent;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class OrderUpdatedEvent implements AutomatableEvent
{
    use Dispatchable, SerializesModels;

    protected Order $order;
    protected array $input;
    protected User|null $user;
    protected Source|null $eventSource;

    /**
     *  Create a new event instance.
     *
     */
    public function __construct(Order $order, array $input, ?User $user, ?Source $eventSource = null)
    {
        $this->order = $order;
        $this->input = $input;
        $this->user = $user;
        $this->eventSource = $eventSource;
    }

    public function getOperation(): Order
    {
        return $this->order;
    }

    public function hasChanged(OrderUpdateField $field): bool
    {
        return $field->hasChange($this->order);
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getEventSource(): ?Source
    {
        return $this->eventSource;
    }

    public static function getTitle(): String
    {
        return 'Order Updated';
    }
}
