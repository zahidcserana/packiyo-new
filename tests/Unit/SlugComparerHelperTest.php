<?php

namespace Tests\Unit;

use App\Components\BillingRates\Helpers\SlugComparerHelper;
use App\Models\ShippingCarrier;
use App\Models\ShippingMethod;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use InvalidArgumentException;
use Tests\TestCase;
use Tests\Unit\Traits\UnitTestSetup;

class SlugComparerHelperTest extends TestCase
{
    use DatabaseTransactions, WithFaker, UnitTestSetup;

    private static int $carrierCount = 0;

    private const CARRIER_NAMES = [
        'DHL',
        'FedEx',
        'EasyPost',
        'UPS'
    ];

    public function testShouldThrowErrorIfTryingToCompareDifferentModels()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Models must be of the same class');
        SlugComparerHelper::compareUsingModels($this->createRandomShippingCarrier(), $this->createRandomShippingCarrier());
    }

    public function testShouldThrowErrorIfTryingClassDoesNotImplementDeletableSluggableInterface()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Class must implement DeletableSluggable interface');

        $randomClass = new class {
            public function getKey()
            {
                return 1;
            }
        };

        SlugComparerHelper::compareByClass($randomClass::class, 1, 2);
    }

    public function testEnsureCacheIsEmpty()
    {
        $shipment = $this->createRandomShippingCarrier();
        $shipmentTwo = $this->createRandomShippingCarrier();

        $this->assertFalse(SlugComparerHelper::elementExistInCached(ShippingCarrier::class, $shipmentTwo->id));
        $this->assertFalse(SlugComparerHelper::elementExistInCached(ShippingCarrier::class, $shipment->id));
    }

    public function testCompareUsingModelsDoesFoundElementsInCachedInformationAfterExecution()
    {
        $shipment = $this->createRandomShippingCarrier();
        $shipmentTwo = $this->createRandomShippingCarrier();

        $result = SlugComparerHelper::compareUsingModels($shipment, $shipmentTwo);

        $this->assertFalse($result);
        $this->assertTrue(SlugComparerHelper::elementExistInCached(ShippingCarrier::class, $shipmentTwo->id));
    }

    public function testCompareUsingModelsDoesFoundElementsInCachedInformationInSecondExecution()
    {
        $shipment = $this->createRandomShippingCarrier();
        $shipmentTwo = $this->createRandomShippingCarrier();
        $shipmentThree = $this->createRandomShippingCarrier();
        $shipmentFour = $this->createRandomShippingCarrier();

        $result = SlugComparerHelper::compareUsingModels($shipment, $shipmentTwo);

        $this->assertFalse($result);

        $this->assertFalse(SlugComparerHelper::elementExistInCached(ShippingCarrier::class, $shipmentThree->id));
        $this->assertFalse(SlugComparerHelper::elementExistInCached(ShippingCarrier::class, $shipmentFour->id));

        $resultTwo = SlugComparerHelper::compareUsingModels($shipment, $shipmentThree);
        $resultThree = SlugComparerHelper::compareUsingModels($shipment, $shipmentFour);
        $this->assertFalse($resultTwo);
        $this->assertFalse($resultThree);

        $this->assertTrue(SlugComparerHelper::elementExistInCached(ShippingCarrier::class, $shipmentThree->id));
        $this->assertTrue(SlugComparerHelper::elementExistInCached(ShippingCarrier::class, $shipmentFour->id));
    }

    public function testCompareUsingModelsReturnsTrueForShippingCarriers()
    {
        $shipment = ShippingCarrier::factory()->create([
            'name' => 'DHL',
            'deleted_at' => today()
        ]);

        $shipmentTwo = ShippingCarrier::factory()->create([
            'name' => 'DHL',
            'deleted_at' => today()
        ]);

        $result = SlugComparerHelper::compareUsingModels($shipment, $shipmentTwo);
        $this->assertTrue($result);
    }

    public function testCompareUsingModelsReturnsTrueForShippingMethods()
    {
        $shipment = ShippingCarrier::factory()->create([
            'name' => 'DHL'
        ]);

        $shipmentTwo = ShippingCarrier::factory()->create([
            'name' => 'DHL'
        ]);

        $shipmentMethod = ShippingMethod::factory()->create([
            'name' => 'Ground Shipping',
            'shipping_carrier_id' => $shipment->id,
            'deleted_at' => today()
        ]);

        $shipmentMethodTwo = ShippingMethod::factory()->create([
            'name' => 'Ground Shipping',
            'deleted_at' => today(),
            'shipping_carrier_id' => $shipmentTwo->id
        ]);

        $result = SlugComparerHelper::compareUsingModels($shipmentMethod, $shipmentMethodTwo);
        $this->assertTrue($result);
    }

    /**
     * Makes sure that we always have a unique carrier name
     *
     * @param  array  $attributes
     * @return ShippingCarrier
     */
    private function createRandomShippingCarrier(array $attributes = []): ShippingCarrier
    {
        self::$carrierCount++;

        return ShippingCarrier::factory()->create([
            'name' => $this->faker->randomElement(self::CARRIER_NAMES) . ' ' . self::$carrierCount,
            ...$attributes,
        ]);
    }
}
