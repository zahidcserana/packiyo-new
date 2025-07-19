<?php

use App\Http\Controllers\FeatureController;
use App\Http\Requests\FormRequest;
use App\Models\Customer;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Pennant\Feature;
use Tests\TestCase;

class FeaturesContext extends TestCase implements Context
{
    use UserSteps;
    use DatabaseTransactions;
    use CustomerSteps;
    use ThreePLSteps;
    use HasScope;
    use FeaturesSteps;

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

    /**
     * @When making request to activate :featureFlag feature
     */
    public function makingRequestToActivateFeature(string $featureFlag)
    {
        $request = FormRequest::make([
            'features' => [
                $featureFlag => 1
            ]
        ]);
        app(FeatureController::class)->update($request);
    }

    /**
     * @Given the instance has the feature flag :featureFlag on
     */
    public function theInstancesHasTheFeatureFlagOn(string $featureFlag): void
    {
        $this->assertTrue(Feature::for('instance')->active($featureFlag));
    }

    /**
     * @Given the instance has the feature flag :featureFlag off
     */
    public function theInstancesHasTheFeatureFlagOff(string $featureFlag): void
    {
        $this->assertTrue(Feature::for('instance')->inactive($featureFlag));
    }

    /**
     * @When making request to activate the following features
     */
    public function makingRequestToActivateTheFollowingFeatures(TableNode $table)
    {
        $features = [];
        foreach ($table->getRows() as $row) {
            [$feature] = $row;
            $features['features'][$feature] = 1;
        }

        $request = FormRequest::make([...$features]);

        app(FeatureController::class)->update($request);
    }

    /**
     * @When making request to deactivate the following features
     */
    public function makingRequestToDeactivateTheFollowingFeatures(TableNode $table)
    {
        $features = [];

        foreach ($table->getRows() as $row) {
            [$feature] = $row;
            $features['features'][$feature] = 0;
        }

        $request = FormRequest::make([...$features]);

        app(FeatureController::class)->update($request);
    }

    /**
     * @When making request to activate :featureFlag feature for customer :customerName
     */
    public function makingRequestToActivateFeatureForCustomer($featureFlag, $customerName)
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $request = FormRequest::make([
            'features' => [],
            'customerFeatures' => [$featureFlag => 1],
            'customer_id' => $customer->id
        ]);

        app(FeatureController::class)->update($request);
    }

    /**
     * @When making request to deactivate :featureFlag feature for customer :customerName
     */
    public function makingRequestToDeactivateFeatureForCustomer($featureFlag, $customerName)
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $request = FormRequest::make([
            'features' => [],
            'customerFeatures' => [$featureFlag => 0],
            'customer_id' => $customer->id
        ]);

        app(FeatureController::class)->update($request);
    }

    /**
     * @Then the instance has the following feature flags enabled
     */
    public function theInstanceHasTheFollowingFeatureFlagsEnabled(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            [$featureFlag] = $row;

            $this->assertTrue(Feature::for('instance')->active($featureFlag), "$featureFlag is disabled");
        }
    }

    /**
     * @Then the instance has the following feature flags disabled
     */
    public function theInstanceHasTheFollowingFeatureFlagsDisabled(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            [$featureFlag] = $row;

            $this->assertTrue(Feature::for('instance')->inactive($featureFlag), "$featureFlag is enabled");
        }
    }

}
