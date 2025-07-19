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
class ReportContext extends TestCase implements Context
{
    use DatabaseTransactions;
    use HasScope;
    use CustomerSteps;
    use UserSteps;
    use ThreePLSteps;
    use BillingSteps;
    use BillingRatesSteps;
    use ProductSteps;
    use PurchaseOrderSteps;
    use OrderSteps;
    use RoutingSteps;
    use LocationSteps;
    use InventorySteps;
    use ReportSteps;
    use FeaturesSteps;

    public function __construct(string $env)
    {
        if (str_contains(strtolower($env), strtolower('prod'))) {
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
    public function seedBeforeScenario(BeforeScenarioScope $scope): void
    {
        $this->seed(UserRolesTableSeeder::class);
        $this->assertDatabaseCount('user_roles', 2); // Not needed in the long run.
        $this->seed(CountriesSeeder::class);
        $this->assertDatabaseCount('countries', 249); // Not needed in the long run.
    }
}
