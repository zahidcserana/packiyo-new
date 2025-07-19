<?php

namespace App\Models\Automations;

use App\Enums\IsoWeekday;
use Illuminate\Support\Facades\Validator;
use Carbon\CarbonImmutable;

final class SetDateFieldActionWeekConfiguration extends SetDateFieldActionConfiguration
{
    public function __construct(protected readonly IsoWeekday $isoWeekday, string $timeOfDay)
    {
        parent::__construct($timeOfDay);
    }

    function toArray(): array
    {
        return [
            self::TIME_OF_DAY => $this->timeOfDay,
            self::ISO_DAY_OF_WEEK => $this->isoWeekday->value
        ];
    }

    protected function validator(): \Illuminate\Validation\Validator
    {
        return Validator::make([
            self::ISO_DAY_OF_WEEK => $this->isoWeekday->value,
            self::TIME_OF_DAY => $this->timeOfDay
        ], [
            self::ISO_DAY_OF_WEEK => ['required', IsoWeekday::ruleIn()],
            ...$this->timeOfDayRules()
        ]);
    }

    public function calculate(CarbonImmutable $from, int $add): CarbonImmutable
    {
        return $from
            ->addWeeks($add)
            ->isoWeekday($this->isoWeekday->value)
            ->setTimeFromTimeString($this->timeOfDay)
            ->utc();
    }
}
