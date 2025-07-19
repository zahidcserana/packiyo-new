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

trait FeaturesSteps
{
    /**
     * @Given the instance has the feature flag :featureFlag disable
     */
    public function theInstancesHasTheFeatureFlagDisable(string $featureFlag): void
    {
        Feature::for('instance')->deactivate($featureFlag);
    }

    /**
     * @Given the instance has the feature flag :featureFlag enabled
     */
    public function theInstancesHasTheFeatureFlagEnabled(string $featureFlag): void
    {
        Feature::for('instance')->activate($featureFlag);
    }
}
