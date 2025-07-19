<?php

namespace App\Models;

use Iterator;

class OrderItemIterator implements Iterator
{
    private array $orderItems;
    private int $position;

    public function __construct(Order $order)
    {
        $this->orderItems = $this->flatten($order);
        $this->position = 0;
    }

    private function flatten(Order $order): array
    {
        $orderItems = [];

        foreach ($order->orderItems as $orderItem) {
            if (!$orderItem->kitOrderItems->isEmpty()) {
                foreach ($orderItem->kitOrderItems as $kitOrderItem) {
                    $orderItems[] = $kitOrderItem;
                }
            } else {
                $orderItems[] = $orderItem;
            }
        }

        return $orderItems;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): mixed
    {
        return $this->orderItems[$this->position];
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->orderItems[$this->position]);
    }
}
