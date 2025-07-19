<?php

namespace App\Models\Automations;

use App\Enums\IsoWeekday;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

abstract class SetDateFieldActionConfiguration implements Arrayable
{
    public const ISO_DAY_OF_WEEK = 'iso_day_of_week';
    public const DAY_OF_MONTH = 'day_of_month';
    public const TIME_OF_DAY = 'time_of_day';

    public function __construct(
        protected readonly string $timeOfDay,
    ) {
        $this->validate();
    }

    protected abstract function validator(): \Illuminate\Validation\Validator;
    abstract public function calculate(CarbonImmutable $from, int $add): CarbonImmutable;

    private function validate(): void
    {
        $validator = $this->validator();

        if (!$validator->passes()) {
            throw new InvalidArgumentException('Invalid text field values');
        }
    }

    protected function timeOfDayRules(): array
    {
        return [
            'time_of_day' => ['required', 'date_format:H:i'],
        ];
    }
}
