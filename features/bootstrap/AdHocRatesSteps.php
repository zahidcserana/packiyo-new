<?php

use App\Models\AutomationActions\ChargeAdHocRateAction;
use App\Models\BillingBalance;
use App\Models\BillingCharge;
use App\Models\BillingRate;
use App\Models\Customer;
use App\Models\RateCard;
use Behat\Gherkin\Node\PyStringNode;
use Illuminate\Database\Eloquent\Builder;

/**
 * Behat steps to test shipment rates.
 */
trait AdHocRatesSteps
{
    /**
     * @Given an ad hoc rate :rateName on rate card :cardName
     */
    public function anAdHocRateOnRateCard(string $rateName, string $cardName): void
    {
        $settings = [
            'description' => 'The temporary description of the ad hoc rate.',
            'unit' => 'hours',
            'fee' => 0.0
        ];
        $rateCard = RateCard::where('name', $cardName)->firstOrFail();
        $this->rateInScope = BillingRate::factory()->for($rateCard)->create([
            'type' => BillingRate::AD_HOC,
            'name' => $rateName,
            'settings' => $settings
        ]);
    }

    /**
     * @Given the ad hoc rate :rateName charges :fee by :adHocUnit with the description
     */
    public function theAdHocRateChargesByWithTheDescription(
        string $rateName, string $fee, string $adHocUnit, PyStringNode $description
    ): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::AD_HOC,
            'name' => $rateName
        ])->firstOrFail();
        $settings = $rate->settings; // Copy array.
        $settings['description'] = $description;
        $settings['unit'] = $adHocUnit;
        $settings['fee'] = $fee;
        $rate->settings = $settings;
        $rate->save();
    }

    /**
     * @Given the automation charges the ad hoc rate :rateName
     */
    public function theAutomationChargesTheAdHocRate(string $rateName): void
    {
        $rate = BillingRate::where([
            'type' => BillingRate::AD_HOC,
            'name' => $rateName
        ])->firstOrFail();
        ChargeAdHocRateAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'billing_rate_id' => $rate->id
        ]);
    }

    /**
     * @Then the client :customerName should have a :rateName ad hoc charge for :amount on :quantity :adHocUnit
     */
    public function theClientShouldHaveAAdHocChargeForOn(
        string $customerName, string $rateName, string $amount, string $quantity, string $adHocUnit
    ): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $balance = BillingBalance::where(['threepl_id' => $customer->parent_id, 'client_id' => $customer->id])->firstOrFail();
        $charges = $balance->billingCharges->filter(fn (BillingCharge $charge)
            => !is_null($charge->billingRate) && $charge->billingRate->type == BillingRate::AD_HOC);

        $this->assertEquals(1, $charges->count());
        $charge = $charges->first();
        $this->assertNotNull($charge->billingRate);
        $this->assertEquals($rateName, $charge->billingRate->name);
        $this->assertEquals((float) $amount, $charge->amount);
        $this->assertEquals($quantity, $charge->quantity);
        $this->assertIsArray($charge->billingRate->settings);
        $this->assertEquals($adHocUnit, $charge->billingRate->settings['unit']);
    }
}
