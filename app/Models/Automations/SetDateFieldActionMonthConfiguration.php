<?php

namespace App\Models\Automations;

use Illuminate\Support\Facades\Validator;
use Carbon\CarbonImmutable;

final class SetDateFieldActionMonthConfiguration extends SetDateFieldActionConfiguration
{
    public function __construct(protected readonly int $day, string $timeOfDay)
    {
        parent::__construct($timeOfDay);
    }

    function toArray(): array
    {
        return [
            self::TIME_OF_DAY => $this->timeOfDay,
            self::DAY_OF_MONTH => $this->day
        ];
    }

    protected function validator(): \Illuminate\Validation\Validator
    {
        return Validator::make([
            self::DAY_OF_MONTH => $this->day,
            self::TIME_OF_DAY => $this->timeOfDay

        ], [
            self::DAY_OF_MONTH => ['required', 'integer', 'between:1,31'],
            ...$this->timeOfDayRules()
        ]);
    }

    public function calculate(CarbonImmutable $from, int $add): CarbonImmutable
    {
        // We can't pass the added months, even if the selected day does not exist in the month, for example:
        // If $from is january 31st and we add 1 month, and set the day to 31, the result must be february 28th

//        return $from->addMonths($add)
//            ->day($this->day)
//            ->setTimeFromTimeString($this->timeOfDay)
//            ->utc();

        // This is the correct way to calculate the date
        $newDate = $from->addMonths($add);

        if ($newDate->daysInMonth < $this->day) {
            $newDate = $newDate->day($newDate->daysInMonth);
        } else {
            $newDate = $newDate->day($this->day);
        }

        $newDate = $newDate->setTimeFromTimeString($this->timeOfDay);

        return $newDate->utc();
    }
}
