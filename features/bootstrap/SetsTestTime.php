<?php

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Illuminate\Support\Carbon;

trait SetsTestTime
{
    protected ?Carbon $testTime = null;

    /** @AfterScenario */
    public function setRealTimeAfterScenario(AfterScenarioScope $scope)
    {
        if (!is_null($this->testTime)) {
            $this->testTime = null;
            Carbon::setTestNow();
        }
    }

    /**
     * @Given the current UTC date is :date and the time is :time
     */
    public function theCurrentUtcDateIsAndTheTimeIs(string $date, string $time): void
    {
        $this->testTime = Carbon::parse($date . ' ' . $time);
        Carbon::setTestNow($this->testTime);
    }

}
