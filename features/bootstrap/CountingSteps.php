<?php

use App\Http\Requests\CycleCountBatch\CycleCountBatchRequest;
use Carbon\Carbon;
use App\Models\{
    Location,
    Product,
    TaskType
};

/**
 * Behat steps to test cycle count.
 */
trait CountingSteps
{
    private ?int $incrementDuration = 1;

    /**
     * @Given I started counting :quantity locations
     */
    public function iStartedCountingLocations($quantity)
    {
        $customer = $this->getCustomerInScope();

        $CycleCountBatchRequest = CycleCountBatchRequest::make([
            'customer_id' => $customer->id,
            'type' => TaskType::TYPE_COUNTING_LOCATIONS,
            'quantity' => $quantity,
        ]);

        $this->cycleCountBatch = app('cycleCountBatch')->availableBatch($CycleCountBatchRequest);
    }

    /**
     * @Given I started counting :quantity products
     */
    public function iStartedCountingProducts($quantity)
    {
        $customer = $this->getCustomerInScope();

        $CycleCountBatchRequest = CycleCountBatchRequest::make([
            'customer_id' => $customer->id,
            'type' => TaskType::TYPE_COUNTING_PRODUCTS,
            'quantity' => $quantity,
        ]);

        $this->cycleCountBatch = app('cycleCountBatch')->availableBatch($CycleCountBatchRequest);
    }

    /**
     * @Then locations order should be :location_order
     */
    public function theLocationsOrderShouldBe(string $locationOrder)
    {
        $expectedOrder = explode(', ', $locationOrder);

        $cycleCountBatch = $this->cycleCountBatch;

        if (empty($cycleCountBatch)) {
            throw new InvalidArgumentException('Expected $cycleCountBatch');
        } else {
            $actualOrder = $cycleCountBatch->cycleCountBatchItems->pluck('location.name')->unique()->values()->toArray();
        }

        $this->assertEquals($expectedOrder, $actualOrder, 'Locations are not in correct order.');
    }

    /**
     * @Then products order should be :product_order
     */
    public function theProductsOrderShouldBe(string $productOrder)
    {
        $expectedOrder = explode(', ', $productOrder);

        $cycleCountBatch = $this->cycleCountBatch;

        if (empty($cycleCountBatch)) {
            throw new InvalidArgumentException('Expected $cycleCountBatch');
        } else {
            $actualOrder = $cycleCountBatch->cycleCountBatchItems->pluck('product.sku')->unique()->values()->toArray();
        }

        $this->assertEquals($expectedOrder, $actualOrder, 'Products are not in correct order.');
    }

    /**
     * @Then the product SKU :sku has priority counting set
     */
    public function theProductSkuHasPriorityCountingSet(string $sku): void
    {
        $this->productInScope = Product::where('sku', $sku)->firstOrFail();
        $this->productInScope->priority_counting_requested_at = Carbon::now()->addSeconds($this->incrementDuration);
        $this->productInScope->last_counted_at = null;
        $this->productInScope->save();
        $this->incrementDuration++;
    }

    /**
     * @Then the product SKU :sku has last counted set
     */
    public function theProductSkuHasLastCountedSet(string $sku): void
    {
        $this->productInScope = Product::where('sku', $sku)->firstOrFail();
        $this->productInScope->priority_counting_requested_at = null;
        $this->productInScope->last_counted_at = Carbon::now()->addSeconds($this->incrementDuration);
        $this->productInScope->save();
        $this->incrementDuration++;
    }

    /**
     * @Then the location name :name has priority counting set
     */
    public function theLocationHasPriorityCountingSet(string $name): void
    {
        $this->locationInScope = Location::where('name', $name)->firstOrFail();
        $this->locationInScope->priority_counting_requested_at = Carbon::now()->addSeconds($this->incrementDuration);
        $this->locationInScope->last_counted_at = null;
        $this->locationInScope->save();
        $this->incrementDuration++;
    }

    /**
     * @Then the location name :name has last counted set
     */
    public function theLocationHasLastCountedSet(string $name): void
    {
        $this->locationInScope = Location::where('name', $name)->firstOrFail();
        $this->locationInScope->priority_counting_requested_at = null;
        $this->locationInScope->last_counted_at = Carbon::now()->addSeconds($this->incrementDuration);
        $this->locationInScope->save();
        $this->incrementDuration++;
    }
}
