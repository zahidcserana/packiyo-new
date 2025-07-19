<?php

namespace App\Traits\Automation;

use App\Models\Automations\AppliesToMany;
use App\Models\Automations\OrderAutomation;
use App\Interfaces\AutomatableEvent;
use App\Models\Automations\OrderTextField;
use App\Models\Automations\TextComparison;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

trait OrderTextFieldConditionTrait
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
            'text_field_values',
            'comparison_operator',
            'case_sensitive'
        ];
    }

    public static function getCastColumns() : array
    {
        return [
            'field_name' => OrderTextField::class,
            'text_field_values' => 'array',
            'comparison_operator' => TextComparison::class,
            'case_sensitive' => 'bool'
        ];
    }

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    public function match(AutomatableEvent $event): bool
    {
        $order = $event->getOperation();
        $matches = false;
        $orderValue = trim(get_nested_value($order, $this->field_name->value));

        if (!$this->case_sensitive) {
            $orderValue = strtolower($orderValue);
        }

        $caseSensitivity = fn (string $text) => $this->case_sensitive ? $text : strtolower($text);
        $equals = fn (string $triggerValue) => $orderValue == $caseSensitivity($triggerValue);
        $startsWith = fn (string $triggerValue) => str_starts_with($orderValue, $caseSensitivity($triggerValue));
        $endsWith = fn (string $triggerValue) => str_ends_with($orderValue, $caseSensitivity($triggerValue));
        $contains = fn (string $triggerValue) => str_contains($orderValue, $caseSensitivity($triggerValue));
        $triggerValues = collect($this->text_field_values);

        if ($this->comparison_operator == TextComparison::SOME_EQUALS) {
            $matches = $triggerValues->contains($equals);
        } elseif ($this->comparison_operator == TextComparison::NONE_EQUALS) {
            $matches = !$triggerValues->contains($equals);
        } elseif ($this->comparison_operator == TextComparison::SOME_STARTS_WITH) {
            $matches = $triggerValues->contains($startsWith);
        } elseif ($this->comparison_operator == TextComparison::NONE_STARTS_WITH) {
            $matches = !$triggerValues->contains($startsWith);
        } elseif ($this->comparison_operator == TextComparison::SOME_ENDS_WITH) {
            $matches = $triggerValues->contains($endsWith);
        } elseif ($this->comparison_operator == TextComparison::NONE_ENDS_WITH) {
            $matches = !$triggerValues->contains($endsWith);
        } elseif ($this->comparison_operator == TextComparison::SOME_CONTAINS) {
            $matches = $triggerValues->contains($contains);
        } elseif ($this->comparison_operator == TextComparison::NONE_CONTAINS) {
            $matches = !$triggerValues->contains($contains);
        }

        return $matches;
    }

    private function isMultiple($values): bool
    {
        return is_array($values) && count($values) > 1;
    }

    private function getFormatedValue($values): string
    {
        if ($this->isMultiple($values)) {
            return '('.implode(',', $values) . ')';
        }

        return is_array($values) ? $values[0] : $values;
    }

    public function getDescriptionAttribute(): string
    {
        return sprintf(
            '%s %s %s',
            $this->getTitleAttribute(),
            TextComparison::getReadableText($this->comparison_operator, $this->isMultiple($this->text_field_values)),
            $this->getFormatedValue($this->text_field_values)
        );
    }
}
