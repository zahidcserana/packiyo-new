<?php

use App\Http\Requests\FormRequest;
use App\Http\Requests\PickingBatch\MultiOrderRequest;
use App\Http\Requests\PickingBatch\PickingBatchRequest;
use App\Http\Requests\PickingBatch\PickRequest;
use Behat\Behat\Tester\Exception\PendingException;
use Carbon\Carbon;
use App\Models\{CustomerSetting,
    CycleCountBatchItem,
    Location,
    LocationProduct,
    Order,
    OrderLock,
    PickingBatch,
    Product,
    Task,
    Tote,
    ToteOrderItem,
    UserSetting,
    Warehouse};
use Behat\Gherkin\Node\TableNode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Behat\Gherkin\Node\PyStringNode;

/**
 * Behat steps to test picking batches.
 */
trait BatchPickingSteps
{
    /**
     * @When I recalculate which orders are ready to ship
     */
    public function iRecalculateWhichOrdersAreReadyToShip(): void
    {
        app('order')->recalculateReadyToShipOrders();
    }

    protected ?PickingBatch $pickingBatch = null;
    protected ?ToteOrderItem $toteOrderItem = null;

    /**
     * @Given I started picking single order :orderNumber
     */
    public function iStartedPickingSingleOrder($orderNumber)
    {
        $customer = $this->getCustomerInScope();
        $customerWarehouseId = app('user')->getCustomerWarehouseId($customer);

        $order = $customer->orders()
            ->where('number', $orderNumber)
            ->firstOrFail();

        $this->pickingBatch = app('routeOptimizer')->findOrCreatePickingBatch(
            $customer->id,
            1,
            PickingBatch::TYPE_SO,
            null,
            null,
            $order->id,
            $customerWarehouseId
        );
    }

    /**
     * @Given a multi-item batch was created to pick :quantity order
     * @Given a multi-item batch was created to pick :quantity orders
     */
    public function aMultiItemBatchWasCreatedToPickQuantityOrders(string $quantity): void
    {
        $customer = $this->getCustomerInScope();
        $customerWarehouseId = app('user')->getCustomerWarehouseId($customer);

        $this->pickingBatch = app('routeOptimizer')->findOrCreatePickingBatch(
            $customer->id,
            $quantity,
            PickingBatch::TYPE_MIB,
            null,
            null,
            null,
            $customerWarehouseId
        );
    }

    /**
     * @Then the picking batch asks me to pick :sku from :locationName location
     */
    public function theProductShouldBePickedFromLocation($sku, $locationName): void
    {
        $pickingBatchItem = $this->pickingBatch->pickingBatchItems()
            ->whereHas('orderItem', fn (Builder $query) => $query->where('sku', $sku))
            ->firstOrFail();

        $this->assertEquals($locationName, $pickingBatchItem->location->name, 'The product can\'t be picked from location.');
    }

    /**
     * @Then the picking batch asks me to pick :sku to :toteName tote
     */
    public function theProductShouldBePickedToTote($sku, $toteName)
    {
        $pickingBatchItemWithSKU = $this->pickingBatch->pickingBatchItems()
            ->with('orderItem', 'toteOrderItems')
            ->whereHas('orderItem', fn (Builder $query) => $query->where('sku', $sku))
            ->firstOrFail();

        $orderId = $pickingBatchItemWithSKU->orderItem->order_id;

        $pickingBatchItems = $this->pickingBatch->pickingBatchItems()
            ->with('orderItem', 'toteOrderItems')
            ->get();

        $existingToteId = $pickingBatchItems
            ->filter(fn ($item) => $item->orderItem->order_id === $orderId)
            ->map(fn ($item) => $item->toteOrderItems->first())
            ->filter()
            ->pluck('tote_id')
            ->first();

        $tote = Tote::where('name', $toteName)->first();

        if (!$tote) {
            throw new Exception("Tote with name $toteName not found.");
        }

        $valid = $existingToteId === $tote->id || ($existingToteId === null && !$tote->placedToteOrderItems()->exists());

        $this->assertTrue($valid, 'The product can\'t be picked to the tote.');
    }

    /**
     * @Then I pick :quantity :sku from :locationName location to :toteName tote
     */
    public function iPickQuantityOfProductFromLocationToTote(int $quantity, string $sku, string $locationName, string $toteName)
    {
        $pickingBatchItem = $this->pickingBatch->pickingBatchItems()
            ->whereHas('orderItem', fn (Builder $query) => $query->where('sku', $sku))
            ->with('orderItem')
            ->firstOrFail();

        $orders = [[
            'id' => $pickingBatchItem->orderItem->order_id,
            'quantity' => $quantity,
            'picking_batch_item_id' => $pickingBatchItem->id
        ]];

        $tote = Tote::where('name', $toteName)->firstOrFail();
        $location = Location::where('name', $locationName)->firstOrFail();

        $pickRequest = PickRequest::make([
            'picking_batch_id' => $this->pickingBatch->id,
            'tote_id' => $tote->id,
            'product_id' => $pickingBatchItem->orderItem->product_id,
            'location_id' => $location->id,
            'orders' => $orders,
            'type' => $this->pickingBatch->type
        ]);

        app('routeOptimizer')->pick($pickRequest);
    }

    /**
     * @Then :quantity :sku from :locationName location should be in :toteName tote
     */
    public function theProductFromLocationShouldBeInTote(int $quantity, string $sku, string $locationName, string $toteName)
    {
        $pickingBatchItem = $this->pickingBatch->pickingBatchItems()
            ->whereHas('orderItem', fn (Builder $query) => $query->where('sku', $sku))
            ->with('orderItem')
            ->firstOrFail();

        $tote = Tote::where('name', $toteName)->firstOrFail();
        $location = Location::where('name', $locationName)->firstOrFail();

        $this->toteOrderItem = ToteOrderItem::where([
            'location_id' => $location->id,
            'tote_id' => $tote->id,
            'picking_batch_item_id' => $pickingBatchItem->id,
            'quantity' => $quantity
        ])->firstOrFail();

        $this->assertTrue(!empty($this->toteOrderItem), 'The product is not in correct tote.');
    }

    /**
     * @Then the picking batch should be completed
     */
    public function thePickingBatchShouldBeCompleted()
    {
        $completedTask = $this->pickingBatch->tasks()
            ->whereNotNull('completed_at')
            ->first();

        $this->assertNotNull($completedTask, 'The picking is not completed.');
    }

    /**
     * @Then the picking batch should be not completed
     */
    public function thePickingBatchShouldNotBeCompleted()
    {
        $completedTask = $this->pickingBatch->tasks()
            ->whereNull('completed_at')
            ->first();

        $this->assertNotNull($completedTask, 'The picking is completed.');
    }

    /**
     * @Then the picking batch contains items from the following orders :orders
     */
    public function thePickingBatchContainsItemsFromTheFollowingOrders(string $orders)
    {
        $orders = explode(',', $orders);
        $orderIsNotFromTheSameWarehouse = true;

        foreach ($this->pickingBatch->pickingBatchItems as $pickingBatchItem) {
            $orderInPickingBatch = Order::find($pickingBatchItem->orderItem->order_id);

            if (!in_array($orderInPickingBatch->number, $orders)) {
                $orderIsNotFromTheSameWarehouse = false;
            }
        }

        $this->assertTrue($orderIsNotFromTheSameWarehouse);
    }

    /**
     * @Given the warehouse :warehouseName is assigned to the picking batch
     */
    public function theWarehouseIsAssignedToThePickingBatch($warehouseName)
    {
        $warehouse = Warehouse::whereHas('contactInformation', static function (Builder $query) use ($warehouseName) {
            $query->where('name', $warehouseName)
                ->where('object_type', Warehouse::class);
        })
            ->firstOrFail();

        $this->assertEquals($warehouse->id, $this->pickingBatch->warehouse_id);
    }

    /**
     * @Then the app should have an audit of :eventName event with the following message
     */
    public function theAppShouldHaveAnAuditWithTheFollowingMessage(string $eventName, PyStringNode $message)
    {
        $audit = $this->toteOrderItem->audits->where('event', $eventName)->where('custom_message', $message)->first();

        $this->assertNotNull($audit);
    }

    /**
     * @When I clear the tote
     */
    public function iClearTheTote()
    {
        app('tote')->clearTote($this->toteOrderItem->tote);
    }
}
