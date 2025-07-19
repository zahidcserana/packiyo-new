<?php

use App\Components\BillingRateComponent;
use App\Http\Controllers\BillingRateController;
use App\Http\Requests\BillingRate\PackagingRateStoreRequest;
use App\Models\BillingRate;
use App\Models\CacheDocuments\PackagingRateShipmentCacheDocument;
use App\Models\Customer;
use App\Models\RateCard;
use App\Models\ShippingBox;
use App\Models\Tag;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use Illuminate\Database\Eloquent\Builder;
use  Illuminate\Http\RedirectResponse;

trait PackagingRatesSteps
{
    public BillingRate|null $packagingRateInScope = null;

    /**
     * @Given the package rate :rateName applies when the order is tagged as :tagName
     */
    public function thePackageRateAppliesWhenTheOrderIsTaggedAs(
        string $rateName, string $tagName, bool $isTagged = true
    ): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::PACKAGING_RATE,
            'name' => $rateName
        ])->firstOrFail();
        Tag::factory()->create([
            'customer_id' => $rate->rateCard->customers()->firstOrFail()->id,
            'name' => $tagName
        ]);
        $settings = $rate->settings; // Copy array.
        $key = $isTagged ? 'match_has_order_tag' : 'match_has_not_order_tag';
        $settings[$key] = [$tagName];
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the package rate :rateName applies when the order is not tagged as :tagName
     */
    public function thePackageRateAppliesWhenTheOrderIsNotTaggedAs($rateName, $tagName): void
    {
        $this->thePackageRateAppliesWhenTheOrderIsTaggedAs($rateName, $tagName, false);
    }

    /**
     * @Given the package rate :arg1 applies when the order is also tagged as :arg2
     */
    public function thePackageRateAppliesWhenTheOrderIsAlsoTaggedAs(
        string $rateName, string $tagName, bool $isTagged = true
    ): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::PACKAGING_RATE,
            'name' => $rateName
        ])->firstOrFail();
        Tag::factory()->create([
            'customer_id' => $rate->rateCard->customers()->firstOrFail()->id,
            'name' => $tagName
        ]);
        $settings = $rate->settings; // Copy array.
        $key = $isTagged ? 'match_has_order_tag' : 'match_has_not_order_tag';
        $settings[$key][] = $tagName;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the package rate :rateName applies when the order is also not tagged as :tagName
     */
    public function thePackageRateAppliesWhenTheOrderIsAlsoNotTaggedAs($rateName, $tagName)
    {
        $this->thePackageRateAppliesWhenTheOrderIsTaggedAs($rateName, $tagName, false);
    }

    /**
     * @Given the package rate :rateName charges :percentage% of the base cost of the shipping box
     */
    public function thePackageRateChargesOfTheBaseCostOfTheLabels($rateName, string $percentage): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::PACKAGING_RATE,
            'name' => $rateName
        ])->firstOrFail();

        $settings = $rate->settings; // Copy array.
        $settings['percentage_of_cost'] = $percentage;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Then :count package charged item for :amount has a quantity of :quantity and the description :description
     */
    public function packageChargedItemForHasAQuantityOfAndTheDescription(
        string $count,
        ?string $amount,
        string $quantity,
        string $description): void
    {
        if (empty($amount)) {
            return;
        }

        $chargeDocument = $this->packagingRateCacheDocument->first();
        $this->checkChargeDocument($chargeDocument, $description, $amount, $quantity, $count);
    }

    /**
     * @Then a new :count package charged item for :amount has a quantity of :quantity and the description :description was generated
     */
    public function aNewPackageChargedItemForHasAQuantityOfAndTheDescriptionWasGenerated(
        string $count,
        ?string $amount,
        string $quantity,
        string $description): void
    {
        if (empty($amount)) {
            return;
        }

        $chargeDocument = PackagingRateShipmentCacheDocument::all()->first();
        $this->checkChargeDocument($chargeDocument, $description, $amount, $quantity, $count);
    }

    /**
     * @Given the package rate :rateName applies when use ship box :shippingBoxName of the customer :customerName
     */
    public function thePackageRateAppliesWhenUseShipBoxOfTheCustomer($rateName, $shippingBoxName, $customerName)
    {
        $customer = $this->getCustomerByName($customerName);
        $shippingBox = $customer->shippingBoxes()->where('name', $shippingBoxName)->firstOrFail();

        $rate = BillingRate::where([
            'type' => BillingRate::PACKAGING_RATE,
            'name' => $rateName
        ])->firstOrFail();

        $settings = $rate->settings; // Copy array.
        $shipping_boxes_selected = json_decode($settings['shipping_boxes_selected'], true);

        $shipping_boxes_selected[$customer->id] = [$shippingBox->id];
        $settings['shipping_boxes_selected'] = json_encode($shipping_boxes_selected);
        $settings['customer_selected'][] = $customer->id;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the package rate :rateName applies when use any of the customer :customerName shipping box
     */
    public function thePackageRateAppliesWhenUseAnyOfTheTheCustomerShippingBox($rateName, $customerName)
    {
        $customer = $this->getCustomerByName($customerName);
        $shippingBoxes = $customer->shippingBoxes()->get();

        $rate = BillingRate::where([
            'type' => BillingRate::PACKAGING_RATE,
            'name' => $rateName
        ])->firstOrFail();

        $settings = $rate->settings; // Copy array.
        $shipping_boxes_selected = json_decode($settings['shipping_boxes_selected'], true);
        $shippingBoxesIds = [];
        foreach ($shippingBoxes as $shippingBox) {
            $shippingBoxesIds[] = $shippingBox->id;
        }
        $shipping_boxes_selected[$customer->id] = $shippingBoxesIds;
        $settings['shipping_boxes_selected'] = json_encode($shipping_boxes_selected);
        $settings['customer_selected'][] = $customer->id;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the package rate :rateName applies when use custom box
     */
    public function thePackageRateAppliesWhenUseCustomBox($rateName)
    {
        $rate = BillingRate::where([
            'type' => BillingRate::PACKAGING_RATE,
            'name' => $rateName
        ])->firstOrFail();

        $settings = $rate->settings; // Copy array.
        $settings['is_custom_packaging'] = true;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the package rate :rateName applies also when use any of the customer :customerName shipping box
     */
    public function thePackageRateAppliesAlsoWhenUseAnyOfTheCustomerShippingBox($rateName, $customerName)
    {
        $this->thePackageRateAppliesWhenUseAnyOfTheTheCustomerShippingBox($rateName, $customerName);
    }


    /**
     * @When I create package rate named :rateName to assign to rate card :rateCardName that uses this matching criteria
     */
    public function iCreatePackageRateNamedToAssignToRateCardThatUsesThisMatchingCriteria($rateName, $rateCardName, TableNode $table): void
    {
        $rateCard = RateCard::where('name', $rateCardName)->firstOrFail();
        $settings = $this->getSettingsForPackageRateFromTable($table);

        $data = [
            'is_enabled' => true,
            'name' => $rateName,
            'code' => (string)rand(500, 6000),
            'settings' => $settings
        ];
        $storeRequest = PackagingRateStoreRequest::make($data);

        [$result, $error] = app(BillingRateComponent::class)->storePackagingRate($storeRequest->validated(), $rateCard);
        if($result instanceof BillingRate){
            $this->assertNull($error);
            $this->packagingRateInScope = $result;
        }else{
            $this->rateCardError = $error;
        }
    }

    /**
     * @Then the rate card :rateCardName should not have a package rate called :labelRateName
     */
    public function theRateCardShouldNotHaveAPackageRateCalled(string $rateCardName, string $labelRateName): void
    {
        $rateCard = RateCard::where('name', $rateCardName)->firstOrFail();
        $this->assertEmpty($rateCard->billingRates()->where('name', $labelRateName)->first() ?? null);
        $this->assertEmpty($this->packagingRateInScope);
    }

    /**
     * @Then the rate card :rateCardName should have a package rate called :labelRateName
     */
    public function theRateCardShouldHaveAPackageRateCalled(string $rateCardName, string $labelRateName): void
    {
        $rateCard = RateCard::where('name', $rateCardName)->firstOrFail();
        $this->assertNotEmpty($rateCard->billingRates()->where('name', $labelRateName)->first());
        $this->assertNotEmpty($this->packagingRateInScope);
    }

    private function getCustomerByName($customerName)
    {
        return Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
    }

    /**
     * @param TableNode $table
     * @return array
     */
    private function getSettingsForPackageRateFromTable(TableNode $table): array
    {
        $matchHasOrderTag = [];
        $matchHasNotOrderTag = [];
        $shippingBoxesSelected = [];
        $customerSelected = [];
        $isCustomPackaging = false;

        $rows = $table->getRows();
        foreach ($rows as $index => $values) {
            if ($index == 0) {
                continue;
            }
            [$customerName, $shippingBox, $matchHasOrderTag, $matchHasNotOrderTag, $customPackagingEnable] = $values;
            if (!empty($matchHasOrderTag)) {
                if (is_string($matchHasOrderTag) && !strpos($matchHasOrderTag, ',')) {
                    $matchHasOrderTag = [$matchHasOrderTag];
                } else {
                    $matchHasOrderTag = explode(',', $matchHasOrderTag);
                }
            } else {
                $matchHasOrderTag = [];
            }

            if (!empty($matchHasNotOrderTag)) {
                if (is_string($matchHasNotOrderTag)) {
                    $matchHasNotOrderTag = [$matchHasNotOrderTag];
                } else {
                    $matchHasNotOrderTag = explode(',', $matchHasNotOrderTag);
                }
            } else {
                $matchHasNotOrderTag = [];
            }

            $customer = $this->getCustomerByName($customerName);
            if ($shippingBox == 'All') {
                //get all shipping boxes
                $shippingBoxes = $customer->shippingBoxes()->get();
                $shippingBoxesIds = [];
                foreach ($shippingBoxes as $shippingBox) {
                    $shippingBoxesIds[] = $shippingBox->id;
                }
            } elseif($shippingBox == 'none' || empty($shippingBox)){
                $shippingBoxesIds = [];
            } else {
                $box = ShippingBox::where(['customer_id' => $customer->id, 'name' => $shippingBox])->firstOrFail();
                $shippingBoxesIds[] = $box->id;
            }

            if(!empty($shippingBoxesIds)){
                $shippingBoxesSelected[$customer->id] = $shippingBoxesIds;
                $customerSelected[] = $customer->id;
            }
            $isCustomPackaging = !empty($customPackagingEnable);
        }

        $shippingBoxesSelected = json_encode($shippingBoxesSelected);
        $customerSelected = json_encode($customerSelected);

        $data = [
            'match_has_order_tag' => $matchHasOrderTag,
            'match_has_not_order_tag' => $matchHasNotOrderTag,
            'if_no_other_rate_applies' => false,
            'charge_flat_fee' => true,
            'flat_fee' => 1.0,
            'percentage_of_cost' => 0.0,
            'shipping_boxes_selected' => $shippingBoxesSelected,
            'customer_selected' => $customerSelected
        ];

        if($isCustomPackaging){
            $data['is_custom_packaging'] = $isCustomPackaging;
        }
        return $data;
    }

    /**
     * @Given the package rate :rateName has a flat fee of :fee
     */
    public function thePackageRateHasAFlatFeeOf(string $rateName, string $fee): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::PACKAGING_RATE,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $settings['charge_flat_fee'] = true;
        $settings['flat_fee'] = $fee;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given a package rate :rateName on rate card :cardName
     */
    public function aPackageRateOnRateCard(string $rateName, string $cardName): void
    {
        $settings = [
            'shipping_boxes_selected' => json_encode([]),
            'match_has_order_tag' => [],
            'match_has_not_order_tag' => [],
            'if_no_other_rate_applies' => false,
            'charge_flat_fee' => false,
            'flat_fee' => 0.0,
            'percentage_of_cost' => 0.0,
            'customer_selected' => []
        ];

        $rateCard = RateCard::where('name', $cardName)->firstOrFail();
        $this->rateInScope = BillingRate::factory()->for($rateCard)->create([
            'type' => BillingRate::PACKAGING_RATE,
            'name' => $rateName,
            'settings' => $settings
        ]);
    }

    /**
     * @param mixed $chargeDocument
     * @param string $description
     * @param string $amount
     * @param string $quantity
     * @param string $count
     * @return void
     */
    private function checkChargeDocument(mixed $chargeDocument, string $description, string $amount, string $quantity, string $count): void
    {
        $charges = collect($chargeDocument->charges);
        $chargedItems = $charges->where('description', '=', $description)
            ->where('total_charge', '=', $amount)
            ->where('quantity', '=', $quantity);

        $this->assertTrue(!$chargedItems->isEmpty(), 'There are no fees matching the criteria.');
        $this->assertEquals($count, $chargedItems->count(), 'There are not exactly ' . $count . ' fees with matching the criteria.');
    }
}
