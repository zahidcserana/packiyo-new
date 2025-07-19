<?php

use App\Components\BillingRateComponent;
use App\Components\ShippingComponent;
use App\Http\Requests\BillingRate\ShipmentsByShippingLabelStoreRequest;
use App\Models\{BillingCharge, BillingRate, InvoiceLineItem, RateCard, Shipment, ShippingCarrier, ShippingMethod, Tag};
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Behat steps to test shipment rates.
 */
trait ShipmentRatesSteps
{
    protected BillingRate|null $rateInScope = null;

    /**
     * @Given a shipping label rate :rateName on rate card :cardName
     */
    public function aShippingLabelRateOnRateCard(string $rateName, string $cardName): void
    {
        $settings = [
            'methods_selected' => json_encode([]),
            'match_has_order_tag' => [],
            'match_has_not_order_tag' => [],
            'if_no_other_rate_applies' => false,
            'charge_flat_fee' => false,
            'flat_fee' => 0.0,
            'percentage_of_cost' => 0.0,
            'carriers_and_methods' => json_encode([])
        ];


        $rateCard = RateCard::where('name', $cardName)->firstOrFail();
        $this->rateInScope = BillingRate::factory()->for($rateCard)->create([
            'type' => BillingRate::SHIPMENTS_BY_SHIPPING_LABEL,
            'name' => $rateName,
            'settings' => $settings
        ]);
    }

    /**
     * @Given no billing card is assign the customer on rate card :cardName
     */
    public function noBillingCardIsAssingTheCustomerOnRateCard($cardName)
    {
        $rateCard = RateCard::where('name', $cardName)->firstOrFail();
        $billingRate = $rateCard->billingRates()->get();
        $billingRate->each->delete();
    }

    /**
     * @Given the shipping label rate :rateName applies when no other rate matches
     */
    public function theShippingLabelRateAppliesWhenNoOtherRateMatches(string $rateName): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_SHIPPING_LABEL,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $settings['if_no_other_rate_applies'] = true;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the shipping label rate :rateName applies when other rate matches
     */
    public function theShippingLabelRateAppliesWhenOtherRateMatches(string $rateName): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_SHIPPING_LABEL,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $settings['if_no_other_rate_applies'] = false;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @When a shipping label rate :rateName was updated at :date
     */
    public function aShippingLabelRateWasUpdatedAt($rateName, $date)
    {
        $rate = BillingRate::where([
        'type' => BillingRate::SHIPMENTS_BY_SHIPPING_LABEL,
        'name' => $rateName
    ])->firstOrFail();
        $rate->updated_at = Carbon::parse($date);
        $rate->save();
    }

    /**
     * @When a packaging rate :rateName was updated at :date
     */
    public function aPackagingRateWasUpdatedAt($rateName, $date)
    {
        $rate = BillingRate::where([
        'type' => BillingRate::PACKAGING_RATE,
        'name' => $rateName
    ])->firstOrFail();
        $rate->updated_at = Carbon::parse($date);
        $rate->save();
    }

    /**
     * @When a picking rate :rateName was updated at :date
     */
    public function aPickingRateWasUpdatedAt($rateName, $date)
    {
        $rate = BillingRate::where([
        'type' => BillingRate::SHIPMENTS_BY_PICKING_RATE_V2,
        'name' => $rateName
    ])->firstOrFail();
        $rate->updated_at = Carbon::parse($date);
        $rate->save();
    }

    /**
     * @Given the shipping label rate :rateName is updated
     */
    public function theShippingLabelRateIsUpdated(string $rateName): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_SHIPPING_LABEL,
            'name' => $rateName
        ])->firstOrFail();
        $rate->updated_at = now();
        $rate->save();
    }

    /**
     * @Given the shipping label rate :rateName applies when the carrier is :carrierName
     */
    public function theShippingLabelRateAppliesWhenTheCarrierIs($rateName, $carrierName)
    {
        $shippingMethod = ShippingMethod::whereHas('shippingCarrier', function (Builder $query) use (&$carrierName) {
            $query->where('name', $carrierName);
        })->firstOrFail();
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_SHIPPING_LABEL,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $methods_selected = json_decode($settings['methods_selected'], true);
        $methods_selected[$shippingMethod->shippingCarrier->id] = [$shippingMethod->id];
        $settings['methods_selected'] = json_encode($methods_selected);
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the shipping label rate :rateName applies when is generic shipping
     */
    public function theShippingLabelRateAppliesWhenIsGenericShipping($rateName)
    {
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_SHIPPING_LABEL,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $settings['is_generic_shipping'] = true;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the shipping label rate :rateName applies when the carrier is :carrierName to all shipment methods
     */
    public function theShippingLabelRateAppliesWhenTheCarrierIsToAllShipmentMethods(string $rateName, string $carrierName): void
    {
        $shippingMethods = ShippingMethod::whereHas('shippingCarrier', function (Builder $query) use (&$carrierName) {
            $query->where('name', $carrierName);
        })->get();
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_SHIPPING_LABEL,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $methodSelected = [];
        $shippingCarrierId = null;
        foreach ($shippingMethods as $shippingMethod) {
            if (empty($shippingCarrierId)) {
                $shippingCarrierId = $shippingMethod->shippingCarrier->id;
            }
            $methodSelected[$shippingMethod->shippingCarrier->id] = [$shippingMethod->id];
        }
        $settings['methods_selected'] = json_encode($methodSelected);
        $settings['carriers_and_methods'] = json_encode([$shippingCarrierId]);
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the shipping label rate :rateName applies when the carrier is :carrierName to all shipment methods and other rate matches
     */
    public function theShippingLabelRateAppliesWhenTheCarrierIsToAllShipmentMethodsAndOtherRateMatches(string $rateName, string $carrierName): void
    {
        $shippingMethods = ShippingMethod::whereHas('shippingCarrier', function (Builder $query) use (&$carrierName) {
            $query->where('name', $carrierName);
        })->get();
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_SHIPPING_LABEL,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $methodSelected = [];
        $shippingCarrierId = null;
        foreach ($shippingMethods as $shippingMethod) {
            if (empty($shippingCarrierId)) {
                $shippingCarrierId = $shippingMethod->shippingCarrier->id;
            }
            $methodSelected[$shippingMethod->shippingCarrier->id] = [$shippingMethod->id];
        }
        $settings['methods_selected'] = json_encode($methodSelected);
        $settings['carriers_and_methods'] = json_encode([$shippingCarrierId]);
        $settings['if_no_other_rate_applies'] = false;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the shipping label rate :rateName applies when the carrier is :carrierName with shipping method :methodName
     */
    public function theShippingLabelRateAppliesWhenTheCarrierIsWithShippingMethod(string $rateName, string $carrierName, string $methodName): void
    {
        $shippingMethod = ShippingMethod::whereHas('shippingCarrier', function (Builder $query) use (&$carrierName) {
            $query->where('name', $carrierName);
        })->where('name', $methodName)->firstOrFail();
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_SHIPPING_LABEL,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $oldMethodsSelected = json_decode($settings['methods_selected']);

        if (array_key_exists($shippingMethod->shippingCarrier->id,$oldMethodsSelected)) {
            $oldMethodsSelected[$shippingMethod->shippingCarrier->id][] = $shippingMethod->id;
            $newMethodsSelected = $oldMethodsSelected;
        }else{
            $newMethodsSelected = $oldMethodsSelected + [$shippingMethod->shippingCarrier->id => [$shippingMethod->id]];
        }

        $settings['methods_selected'] = json_encode($newMethodsSelected);
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the shipping label rate :rateName applies when the order is tagged as :tagName
     */
    public function theShippingLabelRateAppliesWhenTheOrderIsTaggedAs(
        string $rateName, string $tagName, bool $isTagged = true
    ): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_SHIPPING_LABEL,
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
     * @Given the shipping label rate :rateName applies when the order is also tagged as :tagName
     */
    public function theShippingLabelRateAppliesWhenTheOrderIsAlsoTaggedAs(
        string $rateName, string $tagName, bool $isTagged = true
    ): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_SHIPPING_LABEL,
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
     * @Given the shipping label rate :rateName applies when the order is not tagged as :tagName
     */
    public function theShippingLabelRateAppliesWhenTheOrderIsNotTaggedAs(string $rateName, string $tagName): void
    {
        $this->theShippingLabelRateAppliesWhenTheOrderIsTaggedAs($rateName, $tagName, false);
    }

    /**
     * @Given the shipping label rate :rateName applies when the order is also not tagged as :tagName
     */
    public function theShippingLabelRateAppliesWhenTheOrderIsAlsoNotTaggedAs($rateName, $tagName)
    {
        $this->theShippingLabelRateAppliesWhenTheOrderIsAlsoTaggedAs($rateName, $tagName, false);
    }


    /**
     * @Given picking rate :rateName is removed
     */
    public function pickingRateIsRemoved(string $rateName): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_PICKING_RATE_V2,
            'name' => $rateName
        ])->firstOrFail();

        $rate->delete();
    }


    /**
     * @Given a picking rate :rateName on rate card :cardName
     */
    public function aPickingRateOnRateCard(string $rateName, string $cardName): void
    {
        $settings = [
            'match_has_product_tag' => [],
            'match_has_not_product_tag' => [],
            'match_has_order_tag' => [],
            'match_has_not_order_tag' => [],
            'if_no_other_rate_applies' => false,
            'charge_flat_fee' => false,
            'flat_fee' => 0.0,
            'first_pick_fee' => 0.0,
            'charge_additional_sku_picks' => false,
            'additional_sku_pick_fee' => 0.0,
            'pick_range_fees' => [],
            'remaining_picks_fee' => 0.0
        ];
        $rateCard = RateCard::where('name', $cardName)->firstOrFail();
        $this->rateInScope = BillingRate::factory()->for($rateCard)->create([
            'type' => BillingRate::SHIPMENTS_BY_PICKING_RATE_V2,
            'name' => $rateName,
            'settings' => $settings
        ]);
    }

    /**
     * @Given the picking rate :rateName applies when no other rate matches
     */
    public function thePickingRateAppliesWhenNoOtherRateMatches(string $rateName): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_PICKING_RATE_V2,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $settings['if_no_other_rate_applies'] = true;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the picking rate :rateName applies when the order is tagged as :tagName
     */
    public function thePickingRateAppliesWhenTheOrderIsTaggedAs(
        string $rateName, string $tagName, bool $isTagged = true, bool $also = false
    ): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_PICKING_RATE_V2,
            'name' => $rateName
        ])->firstOrFail();
        Tag::factory()->create([
            'customer_id' => $rate->rateCard->customers()->firstOrFail()->id,
            'name' => $tagName
        ]);
        $settings = $rate->settings; // Copy array.
        $key = $isTagged ? 'match_has_order_tag' : 'match_has_not_order_tag';

        if ($also) {
            $settings[$key][] = $tagName;
        } else {
            $settings[$key] = [$tagName];
        }

        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the picking rate :rateName applies when the order is not tagged as :tagName
     */
    public function thePickingRateAppliesWhenTheOrderIsNotTaggedAs(string $rateName, string $tagName): void
    {
        $this->thePickingRateAppliesWhenTheOrderIsTaggedAs($rateName, $tagName, false);
    }

    /**
     * @Given the picking rate :rateName applies when the order is also not tagged as :tagName
     */
    public function thePickingRateAppliesWhenTheOrderIsAlsoNotTaggedAs(string $rateName, string $tagName): void
    {
        $this->thePickingRateAppliesWhenTheOrderIsTaggedAs($rateName, $tagName, false, true);
    }

    /**
     * @Given the picking rate :rateName applies when the order is also tagged as :tagName
     */
    public function thePickingRateAppliesWhenTheOrderIsAlsoTaggedAs(string $rateName, string $tagName): void
    {
        $this->thePickingRateAppliesWhenTheOrderIsTaggedAs($rateName, $tagName, true, true);
    }

    /**
     * @Given the picking rate :rateName applies when the product is tagged as :tagName
     */
    public function thePickingRateAppliesWhenTheProductIsTaggedAs(
        string $rateName, string $tagName, bool $isTagged = true
    ): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_PICKING_RATE_V2,
            'name' => $rateName
        ])->firstOrFail();
        Tag::factory()->create([
            'customer_id' => $rate->rateCard->customers()->firstOrFail()->id,
            'name' => $tagName
        ]);
        $settings = $rate->settings; // Copy array.
        $key = $isTagged ? 'match_has_product_tag' : 'match_has_not_product_tag';
        $settings[$key] = [$tagName];
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the picking rate :rateName applies when the product is not tagged as :tagName
     */
    public function thePickingRateAppliesWhenTheProductIsNotTaggedAs(string $rateName, string $tagName)
    {
        $this->thePickingRateAppliesWhenThePRoductIsTaggedAs($rateName, $tagName, false);
    }

    /**
     * @Given the picking rate :rateName has a fee of :fee for the first pick of an order
     */
    public function thePickingRateHasAFeeOfForTheFirstPickOfAnOrder(string $rateName, string $fee): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_PICKING_RATE_V2,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $settings['first_pick_fee'] = $fee;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the picking rate :rateName has a flat fee of :fee
     */
    public function thePickingRateHasAFlatFeeOf(string $rateName, string $fee): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_PICKING_RATE_V2,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $settings['charge_flat_fee'] = true;
        $settings['flat_fee'] = $fee;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the picking rate :rateName has a fee of :fee for each additional SKU pick
     */
    public function thePickingRateHasAFeeOfForEachAdditionalSkuPick(string $rateName, string $fee): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_PICKING_RATE_V2,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $settings['charge_additional_sku_picks'] = true;
        $settings['additional_sku_pick_fee'] = $fee;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the picking rate :rateName has a fee of :fee for picks :from to :to
     */
    public function thePickingRateHasAFeeOfForPicksTo(string $rateName, string $fee, string $from, string $to): void
    {
        if(empty($from)){
            $from = 2;
        }

        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_PICKING_RATE_V2,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $settings['pick_range_fees'][] = ['fee' => (float) $fee, 'to' => (int) $to, 'from' =>(int)$from]; // $from is not used.
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the picking rate :rateName has a fee of :fee for the remainder picks of an order
     */
    public function thePickingRateHasAFeeOfForTheRemainderPicksOfAnOrder(string $rateName, string $fee): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_PICKING_RATE_V2,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $settings['remaining_picks_fee'] = $fee;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the picking rate :rateName does not have a fee of "" for the remainder picks of an order
     */
    public function thePickingRateDoesNotHaveAFeeOfForTheRemainderPicksOfAnOrder($rateName)
    {
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_PICKING_RATE_V2,
            'name' => $rateName
        ])->firstOrFail();

        $settings = $rate->settings;
        $this->assertTrue(empty($settings['remaining_picks_fee']));
    }


    /**
     * @Given the picking rate :rateName does not have a fee of :arg2 for the first pick of an order
     */
    public function thePickingRateDoesNotHaveAFeeOfForTheFirstPickOfAnOrder($rateName, $arg2)
    {
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_PICKING_RATE_V2,
            'name' => $rateName
        ])->firstOrFail();

        $settings = $rate->settings;
        $this->assertTrue(empty($settings['first_pick_fee'] ));
    }


    /**
     * @Given the picking rate :rateName does not have a flat fee of :arg2
     */
    public function thePickingRateDoesNotHaveAFlatFeeOf2($rateName, $arg2)
    {
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_PICKING_RATE_V2,
            'name' => $rateName
        ])->firstOrFail();

        $settings = $rate->settings;
        $this->assertTrue(empty($settings['flat_fee'] ));
    }

    /**
     * @Given the picking rate :arg1 does not have a fee of :arg2 for each additional SKU pick
     */
    public function thePickingRateDoesNotHaveAFeeOfForEachAdditionalSkuPick($rateName, $arg2)
    {
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_PICKING_RATE_V2,
            'name' => $rateName
        ])->firstOrFail();

        $settings = $rate->settings;
        $this->assertTrue(empty($settings['charge_additional_sku_picks'] ));
    }

    /**
     * @Given the shipping label rate has a flat fee of :fee
     */
    public function theShippingLabelRateHasAFlatFeeOf(string $fee): void
    {
        if (!$this->rateInScope) {
            throw new PendingException('TODO: There is no billing rate in scope yet.');
        }

        $this->rateInScope->refresh();
        $settings = $this->rateInScope->settings; // Copy array.
        $settings['charge_flat_fee'] = true;
        $settings['flat_fee'] = $fee;
        $this->rateInScope->settings = $settings;
        $this->rateInScope->save();
    }

    /**
     * @Given the shipping label rate :rateName has a flat fee of :fee
     */
    public function theShippingLabelRateNameHasAFlatFeeOf(string $rateName, string $fee): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::SHIPMENTS_BY_SHIPPING_LABEL,
            'name' => $rateName
        ])->firstOrFail();

        $settings = $rate->settings; // Copy array.
        $settings['charge_flat_fee'] = true;
        $settings['flat_fee'] = $fee;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the shipping label rate charges :percentage% of the base cost of the labels
     */
    public function theShippingLabelRateChargesOfTheBaseCostOfTheLabels(string $percentage): void
    {
        if (!$this->rateInScope) {
            throw new PendingException('TODO: There is no billing rate in scope yet.');
        }

        $this->rateInScope->refresh();
        $settings = $this->rateInScope->settings; // Copy array.
        $settings['percentage_of_cost'] = $percentage;
        $this->rateInScope->settings = $settings;
        $this->rateInScope->save();
    }

    /**
     * @When any created charges share the timestamp of the corresponding shipment
     */
    public function anyCreatedChargesShareTheTimestampOfTheCorrespondingShipment()
    {
        foreach (BillingCharge::all() as $charge) {
            if (!empty($charge->shipment)) {
                $charge->created_at = $charge->shipment->created_at;
                $charge->save();
            }
        }
    }

    /**
     * @When I create a shipping label rate named :labelRateName to assign to rate card :rateCardName that uses this matching criteria
     */
    public function iCreateAShippingLabelRateNamedThatUsesThisMatchingCriteria(string $labelRateName, string $rateCardName, TableNode $table): void
    {
        $rateCard = RateCard::where('name', $rateCardName)->firstOrFail();
        $settings = $this->getSettingsFromTable($table);

        $data = [
            'is_enabled' => true,
            'name' => $labelRateName,
            'code' => (string)rand(500, 6000),
            'settings' => $settings
        ];

        $storeRequest = ShipmentsByShippingLabelStoreRequest::make($data);

        [$result, $error] = app(BillingRateComponent::class)->storeShippingRate($storeRequest->validated(), $rateCard);
        if($result instanceof BillingRate){
            $this->packageRateInScope = $result;
        }else{
            $this->rateCardError = $error;
        }
    }

    /**
     * @param TableNode $table
     * @return array
     */
    private function getSettingsFromTable(TableNode $table): array
    {
        $matchHasOrderTag = [];
        $matchHasNotOrderTag = [];
        $carriersAndMethod = [];
        $methodsSelected = [];
        $genericShipping = false;

        $rows = $table->getRows();
        foreach ($rows as $index => $values) {
            if ($index == 0) {
                continue;
            }
            [$carrier, $shippingMethod, $orderTag, $notOrderTag, $isGenericShipping] = $values;
            if (!empty($orderTag)) {
                if (is_string($orderTag) && !strpos($orderTag, ',')) {
                    $matchHasOrderTag[] = $orderTag;
                } else {
                    $matchHasOrderTag = explode(',', $orderTag);
                }
            } else {
                if (!empty($matchHasOrderTag)) {
                    $matchHasOrderTag = [];
                }
            }

            if (!empty($notOrderTag)) {
                if (is_string($notOrderTag)) {
                    $matchHasNotOrderTag[] = $notOrderTag;
                } else {
                    $matchHasNotOrderTag = explode(',', $notOrderTag);
                }
            } else {
                if (!empty($matchHasNotOrderTag)) {
                    $matchHasNotOrderTag = [];
                }
            }

            $shippingMethod = ShippingMethod::where('name', $shippingMethod)->first() ?? null;
            $shippingCarrier = ShippingCarrier::where('name', $carrier)->first() ?? null;

            if (!empty($shippingMethod) && !empty($shippingCarrier)) {
                $carriersAndMethod[] = $shippingMethod->id;
                $methodsSelected[$shippingCarrier->id] = [$shippingMethod->id];
            }

            $genericShipping =!empty($isGenericShipping);
        }

        $methodsSelected = json_encode($methodsSelected);

        $data =  [
            'carriers_and_methods' => $carriersAndMethod,
            'match_has_order_tag' => $matchHasOrderTag,
            'match_has_not_order_tag' => $matchHasNotOrderTag,
            'if_no_other_rate_applies' => false,
            'charge_flat_fee' => false,
            'flat_fee' => 0.0,
            'percentage_of_cost' => 0.0,
            'methods_selected' => $methodsSelected,
        ];

        if($genericShipping){
            $data['is_generic_shipping'] = true;
        }
        return $data;
    }

    /**
     * @Then the shipping label rate :rateName billed :chargedAmount for the :carrierName shipment on the date :shippedDate
     */
    public function theShippingLabelRateBilledForTheShipmentOnTheDate(
        string $rateName,
        string $chargedAmount,
        string $carrierName,
        string $shippedDate
    ): void
    {
        $this->assertShipmentCharge(
            BillingRate::SHIPMENTS_BY_SHIPPING_LABEL,
            $rateName,
            $chargedAmount,
            $carrierName,
            $shippedDate
        );
    }

    /**
     * @Then the picking rate :rateName billed :chargedAmount for the :carrierName shipment on the date :shippedDate
     */
    public function thePickingRateBilledForTheShipmentOnTheDate(
        string $rateName,
        string $chargedAmount,
        string $carrierName,
        string $shippedDate
    ): void
    {
        $this->assertShipmentCharge(
            BillingRate::SHIPMENTS_BY_PICKING_RATE_V2,
            $rateName,
            $chargedAmount,
            $carrierName,
            $shippedDate
        );
    }

    /**
     * @Then the shipping box rate :rateName billed :chargedAmount for the :carrierName shipment on the date :shippedDate
     */
    public function theShippingBoxRateBilledForTheShipmentOnTheDate(
        string $rateName,
        string $chargedAmount,
        string $carrierName,
        string $shippedDate
    ): void
    {
        $this->assertShipmentCharge(
            BillingRate::SHIPMENT_BY_BOX,
            $rateName,
            $chargedAmount,
            $carrierName,
            $shippedDate
        );
    }

    protected function assertShipmentCharge(
        string $billingRateType,
        string $rateName,
        string $chargedAmount,
        string $carrierName,
        string $shippedDate
    ): void
    {
        $calculateInvoiceJob = $this->dispatchedJobs[0];

        $rate = BillingRate::where(['type' => $billingRateType, 'name' => $rateName])->firstOrFail();
        $query = Shipment::whereDate('created_at', '=', $shippedDate);

        if ($carrierName == ShippingComponent::SHIPPING_CARRIER_SERVICE_GENERIC) {
            $query->whereNull('shipping_method_id');
        } else {
            $query->whereHas('shippingMethod.shippingCarrier', function (Builder $query) use (&$carrierName) {
                $query->where('name', $carrierName);
            });
        }

        $shipment = $query->firstOrFail();
        $invoiceLineItems = $calculateInvoiceJob->invoice->invoiceLineItems()->where([
            'billing_rate_id' => $rate->id,
            'shipment_id' => $shipment->id
        ])->get();
        $amount = $invoiceLineItems->reduce(fn (float $carry, InvoiceLineItem $item) => $carry += $item->total_charge, 0);
        $this->assertEquals(round($chargedAmount,3), round($amount,3));
    }

    /**
     * @Then the rate card :rateCardName should have a shipping label rate called :labelRateName
     */
    public function theRateCardShouldHaveAShippingLabelRateCalled(string $rateCardName, string $labelRateName): void
    {
        $rateCard = RateCard::where('name', $rateCardName)->firstOrFail();
        $this->assertNotEmpty($rateCard->billingRates()->where('name', $labelRateName)->firstOrFail());
    }
}
