<?php

namespace App\Traits\Automation;

use App\Models\Automations\AppliesToMany;
use App\Models\Automations\OrderAutomation;
use App\Interfaces\AutomatableEvent;
use App\Models\Automations\OrderFlag;
use App\Models\Automations\OrderTextField;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Parental\HasParent;

trait SetTextFieldActionTrait
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
            'text_field_value'
        ];
    }

    public static function getCastColumns() : array
    {
        return [
            'field_name' => OrderTextField::class
        ];
    }

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    public function run(AutomatableEvent $event): void
    {
        $order = $event->getOperation();
        $fieldName = $this->field_name->value;
        $order->$fieldName = $this->text_field_value;
        $order->save();
    }

    public function getDescriptionAttribute(): string
    {
        return sprintf(
            '%s: "%s"',
            $this->getTitleAttribute(),
            $this->text_field_value
        );
    }
}
