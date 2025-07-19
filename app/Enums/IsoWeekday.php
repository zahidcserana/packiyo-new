<?php

namespace App\Enums;

use App\Models\Automations\HasChoices;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

enum IsoWeekday: int
{
    use HasChoices;

    case MONDAY = 1;
    case TUESDAY = 2;
    case WEDNESDAY = 3;
    case THURSDAY = 4;
    case FRIDAY = 5;
    case SATURDAY = 6;
    case SUNDAY = 7;

    public function label(): string
    {
        return match ($this) {
            self::MONDAY => __('Monday'),
            self::TUESDAY => __('Tuesday'),
            self::WEDNESDAY => __('Wednesday'),
            self::THURSDAY => __('Thursday'),
            self::FRIDAY => __('Friday'),
            self::SATURDAY => __('Saturday'),
            self::SUNDAY => __('Sunday'),
        };
    }

    public static function fromLabel(string $label): self
    {
        return match ($label) {
            __('Monday') => self::MONDAY,
            __('Tuesday') => self::TUESDAY,
            __('Wednesday') => self::WEDNESDAY,
            __('Thursday') => self::THURSDAY,
            __('Friday') => self::FRIDAY,
            __('Saturday') => self::SATURDAY,
            __('Sunday') => self::SUNDAY,
            default => throw new \InvalidArgumentException('Invalid label'),
        };
    }

    public static function ruleIn(): In
    {
        $values = array_map(fn (self $case) => $case->value, self::cases());
        return Rule::in($values);
    }
}
