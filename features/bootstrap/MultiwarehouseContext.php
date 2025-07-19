<?php

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
class MultiwarehouseContext extends TestCase implements Context
{
    use DatabaseTransactions;
    use MultipleWarehouseSteps;
    use ProductSteps;
    use OrderSteps;
    use UserSteps;
    use CustomerSteps;
    use BatchPickingSteps;
    use HasScope;
    use ThreePLSteps;

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
        $this->seed(CountriesSeeder::class);
        $this->assertDatabaseCount('countries', 249); // Not needed in the long run.
        $this->seed(UserRolesTableSeeder::class);
        $this->assertDatabaseCount('user_roles', 2);
    }
}
