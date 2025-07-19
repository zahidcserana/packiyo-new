<?php

// namespace Features\Bootstrap;  // TODO: Not finding the context with a namespace - it should.

use Tests\TestCase;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
 use Illuminate\Foundation\Testing\DatabaseTransactions; // Uses transactions but no migrations.

/**
 * Defines application features from the specific context.
 */
class LotTrackingContext extends TestCase implements Context
{
     use DatabaseTransactions; // Faster, only for dev - uses transactions but no migrations.
//    use LazilyRefreshDatabase; // Slower, for CI - resets the db and migrates.
    use CustomerSteps;
    use HasProductInScope;
    use HasScope;
    use HasWarehouseInScope;
    use OrderSteps;
    use PackingSteps;
    use PickingSteps;
    use ProductSteps;
    use PurchaseOrderSteps;
    use SetsTestTime;
    use ThreePLSteps;
    use UserSteps;

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
    public function seedBeforeScenario(BeforeScenarioScope $scope)
    {
        $this->seed(UserRolesTableSeeder::class);
        $this->assertDatabaseCount('user_roles', 2); // Not needed in the long run.
        $this->seed(CountriesSeeder::class);
        $this->assertDatabaseCount('countries', 249); // Not needed in the long run.
    }
}
