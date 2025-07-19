<?php

// namespace Features\Bootstrap;  // TODO: Not finding the context with a namespace - it should.

use Tests\TestCase;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * Defines application features from the specific context.
 */
class WholesaleContext extends TestCase implements Context
{
    use DatabaseTransactions; // Uses transactions, but not migrations.
    // use LazilyRefreshDatabase; // Required for VHS to identify requests using the HTTP body.
    use ThreePLSteps;
    use UserSteps;
    use CustomerSteps;
    use OrderSteps;
    use ProductSteps;
    use WholesaleSteps;
    use WholesaleAPISteps;
    use HasScope;
    use RecordsHTTPRequests;
    use SetsTestTime;
    use CustomerSteps;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct(string $env)
    {
        if (strpos(strtolower($env), strtolower('prod')) !== false) {
            throw new ValueError("You should probably not do that.");
        }

        putenv('APP_ENV=' . $env);

        if (!defined('LARAVEL_START')) define('LARAVEL_START', microtime(true));
    }

    /** @BeforeScenario */
    public function setUpLaravelBeforeScenario(BeforeScenarioScope $scope)
    {
        parent::setUp();
    }

    /** @AfterScenario */
    public function tearDownLaravelAfterScenario(AfterScenarioScope $scope)
    {
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
