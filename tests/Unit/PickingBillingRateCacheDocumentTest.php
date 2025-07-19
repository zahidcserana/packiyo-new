<?php

namespace Tests\Unit;

use App\Models\BillingRate;
use App\Models\CacheDocuments\DataTransferObject\BillingChargeItemDto;
use App\Models\CacheDocuments\PickingBillingRateShipmentCacheDocument;
use App\Models\ContactInformation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\RateCard;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Unit\Traits\UnitTestSetup;

class PickingBillingRateCacheDocumentTest extends TestCase
{
    use DatabaseTransactions, WithFaker, UnitTestSetup;

    /**
     * @description setup simple pickingBillingRateCache for confirming properties
     * @return void
     */
    public function testModelContainsProperties()
    {
        $expectedProperties = ['order_id', 'customer_id', 'charges', 'shippedOrderItems', 'error', 'billingRate'];
        $expectedChargedItemProperties = [
            'billing_rate_id', 'description', 'quantity', 'charge_per_unit', 'total_charge',
            'purchase_order_item_id',
            'purchase_order_id',
            'return_item_id',
            'package_id',
            'package_item_id',
            'shipment_id',
            'location_type_id'];

        $cardName = 'TEST CARD';
        $rateName = 'Test Picking Rate';
        $country = \Countries::where('name', 'United States')->firstOrFail();
        $customer = Customer::factory()->create(['allow_child_customers' => true]);
        ContactInformation::factory()->create([
            'object_type' => Customer::class,
            'object_id' => $customer->id,
            'country_id' => $country->id,
            'name' => $this->faker()->name
        ]);

        $customerChild = Customer::factory()->create(['parent_id' => $customer->id]);
        ContactInformation::factory()->create([
            'object_type' => Customer::class,
            'object_id' => $customer->id,
            'country_id' => $country->id,
            'name' => $this->faker()->name
        ]);

        $order = Order::factory()->create(['customer_id' => $customerChild->id]);
        RateCard::factory()
            ->hasAttached($customerChild, ['priority' => RateCard::PRIMARY_RATE_CARD_PRIORITY], 'customers')
            ->create(['3pl_id' => $customer->id, 'name' => $cardName]);

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
        $billingRate = BillingRate::factory()->for($rateCard)->create([
            'type' => BillingRate::SHIPMENTS_BY_PICKING_RATE_V2,
            'name' => $rateName,
            'settings' => $settings
        ]);

        $charged = new BillingChargeItemDto(
            '',
            $billingRate,
            ['fee' => 1],
            1
        );

        $chargedCacheDocument = PickingBillingRateShipmentCacheDocument::make([$charged], [], $billingRate, $customer, $order->id);
        $this->assertNotNull($chargedCacheDocument);

        $this->assertEquals($expectedProperties, array_keys($chargedCacheDocument->getAttributes()));
        $this->assertIsArray($chargedCacheDocument->getAttributes()['charges']);
        $this->assertIsArray($chargedCacheDocument->getAttributes()['shippedOrderItems']);
        $this->assertIsArray($chargedCacheDocument->getAttributes()['billingRate']);

        foreach ($chargedCacheDocument->getAttributes()['charges'] as $shipment) {
            $this->assertEquals($expectedChargedItemProperties, array_keys($shipment));
        }
    }
}
