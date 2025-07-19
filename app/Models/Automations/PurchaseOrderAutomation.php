<?php

namespace App\Models\Automations;

use App\Events\PurchaseOrderCreatedEvent;
use App\Events\PurchaseOrderReceivedEvent;
use App\Events\PurchaseOrderClosedEvent;
use App\Interfaces\AutomationInterface;
use App\Models\Automation;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Parental\HasParent;

class PurchaseOrderAutomation extends Automation implements AutomationInterface
{
    use HasFactory, HasParent;

    public static function getSupportedEvents(): array
    {
        return [PurchaseOrderCreatedEvent::class, PurchaseOrderReceivedEvent::class, PurchaseOrderClosedEvent::class];
    }

    public static function getOperationClass(): string
    {
        return PurchaseOrder::class;
    }

    public static function getTemplatableAttributes(): array
    {
        return [];
    }
}
