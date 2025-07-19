<?php

namespace Tests\Unit;

use App\Models\CacheDocuments\ShipmentCacheDocument;
use App\Models\ContactInformation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use ReflectionClass;
use Tests\TestCase;
use Tests\Unit\Traits\UnitTestSetup;

class ShipmentCacheDocumentTest extends TestCase
{
    use DatabaseTransactions, WithFaker, UnitTestSetup;

    /**
     * @description simple setup for shipment to get properties
     * @return void
     */
    public function testModelContainsProperties()
    {
        $expectedProperties = ['customer_id', 'order', 'shipping_method', 'shipments'];
        $expectedOrderProperties = ['id', 'number', 'customer_id', 'tags'];
        $expectedShipmentsProperties = ['id', 'shipment_tracking_number', 'packages'];

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
        $shipments = Shipment::factory()->count(2)->create([
            'order_id' => $order->id
        ]);
        $cacheDocument = ShipmentCacheDocument::makeFromModels($order, ...$shipments);
        $this->assertNotNull($cacheDocument);

        $this->assertEquals($expectedProperties, array_keys($cacheDocument->getAttributes()));
        $this->assertEquals($expectedOrderProperties, array_keys($cacheDocument->getAttributes()['order']));
        $this->assertIsArray($cacheDocument->getAttributes()['shipments']);

        foreach ($cacheDocument->getAttributes()['shipments'] as $shipment) {
            $this->assertEquals($expectedShipmentsProperties, array_keys($shipment));
        }
    }
}
