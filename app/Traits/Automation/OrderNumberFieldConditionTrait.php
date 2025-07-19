<?php

namespace App\Traits\Automation;

use App\Models\Automations\AppliesToMany;
use App\Models\Automations\ComparesNumbers;
use App\Models\Automations\NumberComparison;
use App\Models\Automations\OrderAutomation;
use App\Interfaces\AutomatableEvent;
use App\Models\Automations\OrderNumberField;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

trait OrderNumberFieldConditionTrait
{
    use HasFactory, inheritanceHasParent, AppliesToMany, ComparesNumbers;

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
            'comparison_operator',
            'number_field_value'
        ];
    }

    public static function getCastColumns() : array
    {
        return [
            'field_name' => OrderNumberField::class,
            'comparison_operator' => NumberComparison::class
        ];
    }

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    public function match(AutomatableEvent $event): bool
    {
        $operation = $event->getOperation();
        $orderValue = get_nested_value($operation, $this->field_name->value);

        return $this->compare($orderValue, $this->comparison_operator, $this->number_field_value);
    }

    public function getDescriptionAttribute(): String
    {
        return sprintf(
            '%s %s %s',
            $this->getTitleAttribute(),
            NumberComparison::getReadableText($this->comparison_operator),
            $this->number_field_value
        );
    }
}
