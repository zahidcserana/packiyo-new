<?php

namespace App\Models\AutomationActions;

use App\Interfaces\AutomatableEvent;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Interfaces\AutomationActionInterface;
use App\Models\AutomationAction;
use App\Models\Automations\AppliesToMany;
use App\Models\Automations\OrderAutomation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasParent;

class SetWarehouseAction extends AutomationAction implements AutomationActionInterface
{
    use HasFactory, HasParent, AppliesToMany;

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    public static function loadForCommand(): array
    {
        return ['warehouse'];
    }

    public function run(AutomatableEvent $event): void
    {
        $order = $event->getOperation();
        $order->warehouse()->associate($this->warehouse);
        $order->save();
    }

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::class,
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Set warehouse';
    }

    public function getDescriptionAttribute(): string
    {
        return sprintf('%s as %s', $this->getTitleAttribute(), $this->warehouse?->contactInformation?->name);
    }
}
