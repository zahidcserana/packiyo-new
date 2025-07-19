<?php

// namespace Features\Bootstrap;  // TODO: Not finding the context with a namespace - it should.

use App\Models\CacheDocuments\InvoiceCacheDocument;
use App\Models\CacheDocuments\PackagingRateShipmentCacheDocument;
use App\Models\CacheDocuments\ShippingLabelRateShipmentCacheDocument;
use App\Models\RateCard;
use App\Models\BillingRate;
use App\Models\LocationType;
use App\Models\CacheDocuments\PickingBillingRateShipmentCacheDocument;
use App\Models\CacheDocuments\ShipmentCacheDocument;
use App\Models\Order;
use App\Rules\BillingRates\StorageByLocationRule;
use Behat\Gherkin\Node\TableNode;
use Tests\TestCase;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * Defines application features from the specific context.
 */
class BillingContext extends TestCase implements Context
{
    use DatabaseTransactions, DropMongoDocs; // Uses transactions, but not migrations.
    use ThreePLSteps;
    use ShipmentRatesSteps;
    use StorageRatesSteps;
    use UserSteps;
    use BillingSteps;
    use CustomerSteps;
    use OrderSteps;
    use ProductSteps;
    use AutomationSteps;
    use BillingRatesSteps;
    use HasScope;
    use InventorySteps;
    use PurchaseOrderSteps;
    use PurchaseRatesSteps;
    use DropMongoDocs;
    use PackagingRatesSteps;
    use InvoiceSteps;
    use BatchPickingSteps;
    use LocationSteps;
    use FeaturesSteps;
    
    public ?string $rateCardError = null;
    public array $packages = [];

    protected PickingBillingRateShipmentCacheDocument|null $chargeDocument = null;
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
    public function setUpLaravelBeforeScenario(BeforeScenarioScope $scope): void
    {
        parent::setUp();
        ShipmentCacheDocument::truncate();
        PickingBillingRateShipmentCacheDocument::truncate();
        ShippingLabelRateShipmentCacheDocument::truncate();
        PackagingRateShipmentCacheDocument::truncate();
        InvoiceCacheDocument::truncate();
        $this->dropMongoDocs();
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
     * @Given that rate card :cardName has no rates
     */
    public function thatRateCardHasNoRates(string $cardName): void
    {
        $rateCard = RateCard::where('name', $cardName)->firstOrFail();
        $this->assertTrue($rateCard->billingRates()->get()->isEmpty());
    }

    /**
     * @Then I should have gotten the error :message
     */
    public function iShouldHaveGottenTheError($message)
    {
        $this->assertEquals($message, $this->rateCardError);
    }

    /**
     * @Then the rate card :rateCardName should not have a rate called :labelRateName
     */
    public function theRateCardShouldNotHaveARateCalled(string $rateCardName, string $labelRateName): void
    {
        $rateCard = RateCard::where('name', $rateCardName)->firstOrFail();
        $this->assertEmpty($rateCard->billingRates()->where('name', $labelRateName)->first() ?? null);
    }

    /**
     * @Given the picking rate :arg1 does not have a flat fee of :arg2
     */
    public function thePickingRateDoesNotHaveAFlatFeeOf2($arg1, $arg2)
    {
        //does nothing
    }


    /**
     * @Given the picking rate :arg1 does not have a fee of :arg2 for the first pick of an order
     */
    public function thePickingRateDoesNotHaveAFeeOfForTheFirstPickOfAnOrder($arg1, $arg2)
    {
        //does nothing
    }

    /**
     * @Given the picking rate :arg1 does not have a fee of "" for the remainder picks of an order
     */
    public function thePickingRateDoesNotHaveAFeeOfForTheRemainderPicksOfAnOrder($arg1)
    {
        //does nothing
    }

    /**
     * @Given the picking rate :arg1 does not have a fee of :arg2 for each additional SKU pick
     */
    public function thePickingRateDoesNotHaveAFeeOfForEachAdditionalSkuPick($arg1, $arg2)
    {
        //does nothing
    }


    /**
     * @Then :documentCount picking billing rate cache for order number :orderNumber is generated
     */
    public function pickingBillingRageDocumentForOrderNumberIsGenerated($documentCount, $orderNumber)
    {
        $order = Order::where('number', $orderNumber)->first();
        $pickingBillingRateDocument = PickingBillingRateShipmentCacheDocument::where('order_id', $order->id)->get();
        $this->assertEquals($documentCount, $pickingBillingRateDocument->count());
        $this->setChargedDocument($pickingBillingRateDocument->first());
    }

    public function setChargedDocument($document): void
    {
        $this->chargeDocument = $document;
    }

    /**
     * @Given there is not a storage by location rate :rateLabel on rate card :rateCardName
     */
    public function thereIsNotAStorageByLocationRateOnRateCard($rateLabel, $rateCardName)
    {
        $rateCard = RateCard::whereName($rateCardName)->first();

        $billingRate = BillingRate::where(
            [
                'rate_card_id' => $rateCard->id,
                'name' => $rateLabel
            ]
        )->first();

        $this->assertEmpty($billingRate);
    }

    /**
     * @When I create a storage label rate named :rateLabel to assign to rate card :rateCardName that uses this matching criteria
     */
    public function iCreateAStorageLabelRateNamedToAssignToRateCardThatUsesThisMatchingCriteria($rateLabel, $rateCardName, TableNode $table)
    {
        $data = [
            'is_enabled' => 1,
            'name' => $rateLabel,
            'code' => (string)rand(500, 9000)
        ];
        $settings = [
            'fee' => 5,
            'period' => 'month',
        ];

        $location_types = [];
        foreach ($table->getRows() as $key => $row) {
            if ($key == 0) {
                continue;
            }
            [$noLocation, $locationTypes] = $row;
            $settings['no_location'] = $noLocation == "true";

            if (!empty($locationTypes)) {
                if (strstr($locationTypes, ',')) {

                    $values = explode(",", $locationTypes);
                    foreach ($values as $value) {
                        $location_types = $this->getLocationTypeId($value, $location_types);
                    }
                } else {
                    $location_types = $this->getLocationTypeId($locationTypes, $location_types);
                }
            }

        }
        $settings['location_types'] = json_encode($location_types);
        $data['settings'] = $settings;
        $rateCard = RateCard::whereName($rateCardName)->first();
        $rule = new StorageByLocationRule($rateCard, BillingRate::STORAGE_BY_LOCATION, null);
        $result = $rule->passes('settings', $settings);

        if (!$result) {
            $this->rateCardError = $rule->message();
        } else {
            app('billingRate')->store($data, BillingRate::STORAGE_BY_LOCATION, $rateCard);
        }
    }

    /**
     * @Then the rate card :rateCardName should have a storage label rate called :rateLabel
     */
    public function theRateCardShouldHaveAStorageLabelRateCalled($rateCardName, $rateLabel)
    {
        $rateCard = RateCard::whereName($rateCardName)->first();

        $billingRate = BillingRate::where(
            [
                'rate_card_id' => $rateCard->id,
                'name' => $rateLabel
            ]
        )->first();

        $this->assertNotEmpty($billingRate);
    }
}
