<?php

namespace App\Console\Commands\CreateAutomation;

use App\Enums\IsoWeekday;
use App\Models\AutomationActions\SetDateFieldAction;
use App\Models\Automations\OrderDateField;
use App\Models\Automations\SetDateFieldActionConfiguration;
use App\Models\Automations\SetDateFieldActionDayConfiguration;
use App\Models\Automations\SetDateFieldActionMonthConfiguration;
use App\Models\Automations\SetDateFieldActionWeekConfiguration;
use App\Models\Automations\TimeUnit;
use App\Models\Customer;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Validator;

trait AddsSetDateFieldAction
{
    protected function addSetDateFieldAction(AutomationChoices $automationChoices): SetDateFieldAction
    {
        $warehouseId = $this->toWhichWarehouse($automationChoices->getOwnerCustomer());
        $fieldName = $this->whichFieldName();
        $timeUnit = $this->whichTimeUnit();
        $amount = $this->howManyUnits($timeUnit);
        $textFieldValues = $this->handleTimeUnit($timeUnit);

        $action = new SetDateFieldAction([
            'field_name' => $fieldName,
            'number_field_value' => $amount,
            'unit_of_measure' => $timeUnit,
            'text_field_values' => $textFieldValues,
        ]);

        $action->warehouse()->associate($warehouseId);

        return $action;
    }

    private function whichFieldName(): OrderDateField
    {
        $choice = $this->choice(
            __('Which field do you want to set?'),
            OrderDateField::choices()
        );

        return OrderDateField::from($choice);
    }

    private function whichTimeUnit(): TimeUnit
    {
        $choice = $this->choice(
            __('Which time unit should be added to the order\'s creation timestamp?'),
            TimeUnit::choices([
                TimeUnit::BUSINESS_DAYS,
                TimeUnit::YEARS
            ])
        );

        return TimeUnit::from($choice);
    }

    public function howManyUnits(TimeUnit $timeUnit): int
    {
        do {
            $amount = $this->ask(__('How many :time_unit should be added?', ['time_unit' => $timeUnit->value]));
        } while (!filter_var($amount, FILTER_VALIDATE_INT));

        return (int) $amount;
    }

    private function handleTimeUnit(TimeUnit $timeUnit): SetDateFieldActionConfiguration|null
    {
        return match ($timeUnit) {
            TimeUnit::DAYS => $this->handleDays(),
            TimeUnit::WEEKS => $this->handleWeeks(),
            TimeUnit::MONTHS => $this->handleMonths(),
            default => null
        };
    }

    private function handleDays(): SetDateFieldActionConfiguration
    {
        $timeOfDay = $this->whatTimeOfDay();
        return new SetDateFieldActionDayConfiguration($timeOfDay);
    }

    private function handleWeeks(): SetDateFieldActionConfiguration
    {
        $dayOfWeek = $this->choice(
            __('Which day of the week should the field be set to?'),
            IsoWeekday::choices(),
        );
        $timeOfDay = $this->whatTimeOfDay();

        return new SetDateFieldActionWeekConfiguration(IsoWeekday::fromLabel($dayOfWeek), $timeOfDay);
    }

    private function handleMonths(): SetDateFieldActionConfiguration
    {
        $dayOfMonth = $this->ask(
            __('Which day of the month should the field be set to?'),
            '1'
        );
        $timeOfDay = $this->whatTimeOfDay();

        return new SetDateFieldActionMonthConfiguration($dayOfMonth, $timeOfDay);
    }

    private function whatTimeOfDay(): string
    {
        do {
            $result = $this->ask(__('Which time of day should the field be set to?'), '08:00');
            $validator = Validator::make(['time' => $result], ['time' => 'date_format:H:i']);
        } while (!$validator->passes());

        return $result;
    }

    private function toWhichWarehouse(Customer $owner)
    {
        $warehouses = $owner->warehouses()
            ->with('contactInformation')
            ->get(['id']);

        $choice = $this->choice(
            __('Which warehouse\'s time zone should be used for the time of the day?'),
            $warehouses
                ->mapWithKeys(fn (Warehouse $warehouse) => [$warehouse->id => $warehouse->contactInformation->name])
                ->toArray(),
            $warehouses->first()->contactInformation->name
        );

        return $warehouses->first(fn (Warehouse $warehouse) => $warehouse->contactInformation->name == $choice);
    }
}
