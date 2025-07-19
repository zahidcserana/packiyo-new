<?php

use App\Models\AutomationActions\ChargeShippingBoxRateAction;
use App\Models\Automations\AppliesToLineItems;
use App\Models\BillingBalance;
use App\Models\BillingCharge;
use App\Models\Customer;
use App\Models\ShippingBox;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BillingCharges\ShippingBoxCharge;
use App\Models\ShipmentTracking;

/**
 * Behat steps to test billing rates.
 */
trait BillingRatesSteps
{
    /**
     * @Given the automation charges :amount for each box of any kind shipped
     */
    public function theAutomationChargesForEachBoxOfAnyKindShipped(string $amount): void
    {
        ChargeShippingBoxRateAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'shipping_box_id' => null,
            'applies_to' => AppliesToLineItems::ALL,
            'amount' => $amount
        ]);
    }

    /**
     * @Given the automation charges :amount for each shipped :boxName box
     */
    public function theAutomationChargesForEachShippedBoxWithTheDescription(string $amount, string $boxName): void
    {
        $box = ShippingBox::where('name', $boxName)->firstOrFail();
        ChargeShippingBoxRateAction::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'shipping_box_id' => $box->id,
            'applies_to' => AppliesToLineItems::SOME,
            'amount' => $amount
        ]);
    }

    /**
     * @Given the client :customerName has a shipping box charge for :amount and quantity :quantity for tracking :number
     */
    public function theClientHasAShippingBoxChargeForAndQuantityForTracking(
        string $customerName, string $amount, string $quantity, string $number
    ): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $balance = BillingBalance::where(['threepl_id' => $customer->parent_id, 'client_id' => $customer->id])->firstOrFail();
        $tracking = ShipmentTracking::where('tracking_number', $number)->firstOrFail();
        ShippingBoxCharge::factory()->create([
            'automation_id' => $this->automationInScope->id,
            'billing_balance_id' => $balance->id,
            'shipment_id' => $tracking->shipment->id,
            'quantity' => $quantity,
            'amount' => $amount,
            'created_at' => $tracking->shipment->created_at,
            'updated_at' => $tracking->shipment->updated_at
        ]);
    }

    /**
     * @Then the client :customerName should have a shipping box charge for :amount and quantity :quantity
     */
    public function theClientShouldHaveAShippingBoxChargeForAndQuantity(string $customerName, string $amount, string $quantity): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $balance = BillingBalance::where(['threepl_id' => $customer->parent_id, 'client_id' => $customer->id])->firstOrFail();
        $charges = $balance->billingCharges->filter(fn (BillingCharge $charge) => !is_null($charge->shipment));

        $this->assertEquals(1, $charges->count());
        $charge = $charges->first();
        $this->assertNotNull($charge->automation);
        $this->assertNotNull($charge->shipment);
        $this->assertEquals((float) $amount, $charge->amount);
        $this->assertEquals($quantity, $charge->quantity);
    }
}
