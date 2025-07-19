<?php

// namespace Features\Bootstrap;  // TODO: Not finding the context with a namespace - it should.

use Tests\DuskTestCase;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;

/**
 * Defines application features from the specific context.
 */
class LoginContext extends DuskTestCase implements Context
{
    use DatabaseTruncation;
    use BrowserSteps;
    use CustomerSteps;
    use UserSteps;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct(string $env, string $webDriverUrl = null)
    {
        if (strpos(strtolower($env), strtolower('prod')) !== false) {
            throw new ValueError("You should probably not do that.");
        }

        putenv('APP_ENV=' . $env);
        putenv('DUSK_DRIVER_URL=' . $webDriverUrl); // TODO: Remove?
        $_ENV['DUSK_DRIVER_URL'] = $webDriverUrl;
    }

    /**
     * Boot the testing helper traits.
     *
     * @return array
     */
    protected function setUpTraits()
    {
        $this->truncateDatabaseTables();

        return parent::setUpTraits();
    }

    /** @BeforeScenario */
    public function setUpLaravelBeforeScenario(BeforeScenarioScope $scope)
    {
        parent::setUp();
        parent::startChromeDriver();
    }

    /** @AfterScenario */
    public function tearDownLaravelAfterScenario(AfterScenarioScope $scope)
    {
        parent::tearDownDuskClass();
        parent::tearDown();
    }

    /** @BeforeScenario */
    public function seedBeforeScenario(BeforeScenarioScope $scope)
    {
        $this->seed(UserRolesTableSeeder::class);
        $this->assertDatabaseCount('user_roles', 2); // Not needed in the long run.
        $this->seed(CountriesSeeder::class);
        $this->assertDatabaseCount('countries', 249); // Not needed in the long run.
    }
}
