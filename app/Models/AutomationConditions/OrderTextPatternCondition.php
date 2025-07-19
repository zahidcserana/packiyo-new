<?php

namespace App\Models\AutomationConditions;

use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomationBaseObjectInterface;
use App\Interfaces\AutomationConditionInterface;
use App\Models\AutomationCondition;
use App\Models\Automations\AppliesToMany;
use App\Models\Automations\OrderAutomation;
use App\Models\Automations\OrderTextField;
use App\Models\Automations\PatternComparison;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderTextPatternCondition extends AutomationCondition
    implements AutomationConditionInterface, AutomationBaseObjectInterface
{
    use HasFactory, inheritanceHasParent, AppliesToMany;

    public const PATTERN_TO_REGEX = [
        '\{\#\}' => '\d', // One number.
        '\{@\}' => '\w', // One letter.
        '\{\#\?\}' => '\d?', // Zero or one number.
        '\{@\?\}' => '\w?', // Zero or one letter.
        '\{\#\+\}' => '\d+?', // One or more numbers.
        '\{@\+\}' => '\w+?', // One or more letters.
        '\{\#\+\?\}' => '\d*?', // Zero or more numbers.
        '\{@\+\?\}' => '\w*?' // Zero or more letters.
    ];

    protected $fillable = [
        'field_name',
        'text_pattern',
        'comparison_operator'
    ];

    protected $casts = [
        'field_name' => OrderTextField::class,
        'comparison_operator' => PatternComparison::class
    ];

    public static function getSupportedEvents(): array
    {
        return OrderAutomation::getSupportedEvents();
    }

    public function match(AutomatableEvent $event): bool
    {
        $order = $event->getOperation();
        $value = trim(get_nested_value($order, $this->field_name->value));
        $regex = static::patternForComparison($this->text_pattern, $this->comparison_operator);
        $isNot = PatternComparison::isAnyNot($this->comparison_operator);
        $match = preg_match($regex, $value);

        return $isNot ? !$match : (bool) $match;
    }

    protected static function patternForComparison(string $pattern, $operator): string
    {
        $regex = strtr(preg_quote($pattern), static::PATTERN_TO_REGEX);

        if (PatternComparison::isAnyMatches($operator)) {
            $regex = '^' . $regex . '$';
        } elseif (PatternComparison::isAnyStartsWith($operator)) {
            $regex = '^' . $regex;
        } elseif (PatternComparison::isAnyEndsWith($operator)) {
            $regex = $regex . '$';
        }

        return '/' . $regex . '/';
    }

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::getTriggerPathByCondition(self::class),
        ];
    }

    public function getTitleAttribute(): string
    {
        return 'Order Text Pattern';
    }

    public function getDescriptionAttribute(): String
    {
        return $this->getTitleAttribute();
    }
}
