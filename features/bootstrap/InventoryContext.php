<?php

// namespace Features\Bootstrap;  // TODO: Not finding the context with a namespace - it should.

use App\Models\OccupiedLocationLog;
use Database\Seeders\CountriesSeeder;
use Database\Seeders\UserRolesTableSeeder;
use App\Models\Customer;
use App\Models\CacheDocuments\WarehouseOccupiedLocationTypesCacheDocument;
use Illuminate\Database\Eloquent\Builder;
use Tests\TestCase;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * Defines application features from the specific context.
 */
class InventoryContext extends TestCase implements Context
{
    use DatabaseTransactions, DropMongoDocs; // Uses transactions, but not migrations.
    use ThreePLSteps;
    use ShipmentRatesSteps;
    use StorageRatesSteps;
    use RoutingSteps;
    use HasValidationErrors;
    use FeaturesSteps;
    use UserSteps;
    use BillingSteps;
    use CustomerSteps;
    use OrderSteps;
    use ProductSteps;
    use LoggingSteps;
    use HasProductInScope;
    use HasScope;
    use HasWarehouseInScope;
    use SetsTestTime;
    use InventorySteps;
    use PackingSteps;

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
        $this->dropMongoDocs();

        // TODO: Reactivate for calculating occupied inventory locations using MongoDB.
        // https://github.com/mongodb/laravel-mongodb/tree/3.8#database-testing doesn't work well.
         OccupiedLocationLog::truncate();
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

    /**
     * @When I calculate the locations occupied by the client :customerName on the date :date
     */
    public function iCalculateTheLocationsOccupiedByTheClientOnTheDate(string $customerName, string $date): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        App::make('inventoryLog')->calculateLocationsOccupiedByCustomer(
            $customer, $customer->parent->warehouses->first(), Carbon::parse($date)
        );
    }

    /**
     * @Then the client :customerName should have occupied :quantity locations on the date :date
     */
    public function theClientShouldHaveOccupiedLocationsOnTheDate(string $customerName, string $quantity, string $date): void
    {
        $logs = OccupiedLocationLog::where([
            'product.customer.name' => $customerName,
            'calendar_date' => Carbon::parse($date)->toDateString()
        ])
        ->groupBy('location_id')
        ->get(['location_id', 'location', 'warehouse_id']);

        $this->assertCount($quantity, $logs->toArray());
    }

    /**
     * @When I calculate the locations occupied by all clients
     */
    public function iCalculateTheLocationsOccupiedByAllClients(): void
    {
        App::make('inventoryLog')->calculateOccupiedLocations();
    }
}
