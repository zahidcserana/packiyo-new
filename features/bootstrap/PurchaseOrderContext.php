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
class PurchaseOrderContext extends TestCase implements Context
{
    use DatabaseTransactions; // Uses transactions, but not migrations.
    use HasScope;
    use HasWarehouseInScope;
    use SetsTestTime;
    use ThreePLSteps;
    use PackingSteps;
    use CustomerSteps;
    use UserSteps;
    use CustomerSteps;
    use OrderSteps;
    use ProductSteps;
    use PurchaseOrderSteps;
    use PublicApiSteps;

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
