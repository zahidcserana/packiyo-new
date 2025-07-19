<?php

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Illuminate\Support\Facades\Log;
use Illuminate\Log\Events\MessageLogged;

/**
 * Behat steps to test products.
 */
trait LoggingSteps
{
    static $messages = [];

    /** @BeforeScenario */
    public function listenToLogsBeforeScenario(BeforeScenarioScope $scope)
    {
        Log::listen(fn (MessageLogged $message) => static::$messages[] = $message);
    }

    /**
     * @Then the app should have logged a :level with the following message
     * @Then the app should have logged an :level with the following message
     */
    public function theAppShouldHaveLoggedTheFollowingMessage(string $level, PyStringNode $message): void
    {
        $messages = collect(static::$messages)->filter(
            fn (MessageLogged $logged) => $logged->level == $level && $logged->message == $message
        )->unique('message');

        $this->assertEquals(1, $messages->count());
    }
}
