<?php

// namespace Features\Bootstrap;  // TODO: Not finding the context with a namespace - it should.

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Database\Seeders\CountriesSeeder;
use Database\Seeders\UserRolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Defines application features from the specific context.
 */
class EasypostContext extends TestCase implements Context
{
    use DatabaseTransactions; // Uses transactions, but not migrations.
    // use LazilyRefreshDatabase; // Required for VHS to identify requests using the HTTP body.
    use ThreePLSteps;
    use PackingSteps;
    use CustomerSteps;
    use HasWarehouseInScope;
    use UserSteps;
    use CustomerSteps;
    use OrderSteps;
    use ProductSteps;
    use EasypostSteps;
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
        if (str_contains(strtolower($env), strtolower('prod'))) {
            throw new ValueError("You should probably not do that.");
        }

        putenv('APP_ENV=' . $env);

        parent::__construct();
    }

    /** @BeforeScenario */
    public function setUpLaravelBeforeScenario(BeforeScenarioScope $scope): void
    {
        parent::setUp();
    }

    /** @AfterScenario */
    public function tearDownLaravelAfterScenario(AfterScenarioScope $scope): void
    {
        parent::tearDown();
    }

    /** @BeforeScenario */
    public function seedBeforeScenario(BeforeScenarioScope $scope): void
    {
        $this->seed(UserRolesTableSeeder::class);
        $this->seed(CountriesSeeder::class);
    }
}
