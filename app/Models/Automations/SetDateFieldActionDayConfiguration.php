<?php

namespace App\Models\Automations;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;

final class SetDateFieldActionDayConfiguration extends SetDateFieldActionConfiguration
{
    protected function validator(): \Illuminate\Validation\Validator
    {
        return Validator::make([
            self::TIME_OF_DAY => $this->timeOfDay
        ], $this->timeOfDayRules());
    }

    function toArray(): array
    {
        return [
            self::TIME_OF_DAY => $this->timeOfDay
        ];
    }

    public function calculate(CarbonImmutable $from, int $add): CarbonImmutable
    {
        return $from->addDays($add)
            ->setTimeFromTimeString($this->timeOfDay)
            ->utc();
    }
}
