<?php

namespace App\Traits\Automation;

use App\Models\Automations\AppliesToMany;
use App\Models\Automations\OrderAutomation;
use App\Interfaces\AutomatableEvent;
use App\Models\Automations\OrderFlag;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

trait SetFlagActionTrait
{
    use HasFactory, inheritanceHasParent, AppliesToMany;

    public function __construct(array $attributes = [])
    {
        $this->fillable = self::getFillableColumns();
        $this->casts = self::getCastColumns();

        parent::__construct($attributes);
    }

    public static function getFillableColumns() : array
    {
        return [
            'field_name',
            'flag_value'
        ];
    }

    public static function getCastColumns() : array
    {
        return [
            'field_name' => OrderFlag::class,
            'flag_value' => 'bool'
        ];
    }

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    public function run(AutomatableEvent $event): void
    {
        $order = $event->getOperation();
        $flagName = $this->field_name->value;
        $order->$flagName = $this->flag_value;
        $order->save();
    }

    public function getDescriptionAttribute(): String
    {
        return $this->getTitleAttribute();
    }
}
