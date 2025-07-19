<?php

namespace App\Models\Automations;

use App\Events\OrderAgedEvent;
use App\Events\OrderCreatedEvent;
use App\Events\OrderShippedEvent;
use App\Events\OrderUpdatedEvent;
use App\Interfaces\AutomationInterface;
use App\Models\Automation;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Parental\HasParent;

class OrderAutomation extends Automation implements AutomationInterface
{
    use HasFactory, HasParent;

    public static function getSupportedEvents(): array
    {
        return [
            OrderCreatedEvent::class,
            OrderUpdatedEvent::class,
            OrderAgedEvent::class,
            OrderShippedEvent::class
        ];
    }

    public static function getOperationClass(): string
    {
        return Order::class;
    }

    public static function getTemplatableAttributes(): array
    {
        return array_map(fn (OrderTextField $field) => $field->value, OrderTextField::cases());
    }
}
