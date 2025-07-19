<?php

namespace App\Models\AutomationConditions;

use App\Enums\Source;
use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomationBaseObjectInterface;
use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationCondition;
use App\Models\Automations\AppliesToMany;
use App\Models\Automations\OrderAutomation;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderIsManualCondition extends AutomationCondition
    implements AutomationConditionInterface, AutomationBaseObjectInterface
{
    use HasFactory, inheritanceHasParent, AppliesToMany;

    protected $fillable = [
        'flag_value'
    ];

    protected $casts = [
        'flag_value' => 'bool'
    ];

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    public function match(AutomatableEvent $event): bool
    {
        $order = $event->getOperation();
        $source = $order->getSource();

        if (!empty($source)) {
            return in_array($source, [Source::MANUAL_VIA_FORM, Source::MANUAL_VIA_FILE_UPLOAD]);
        }

        $orderIsManual = is_null($order->orderChannel);

        return $this->flag_value ?  $orderIsManual : !$orderIsManual;
    }

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::getTriggerPathByCondition(self::class),
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Order is manual';
    }

    public function getDescriptionAttribute(): String
    {
        return $this->getTitleAttribute();
    }
}
