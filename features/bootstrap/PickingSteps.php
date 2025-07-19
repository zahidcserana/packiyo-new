<?php

use App\Http\Requests\PickingBatch\SingleOrderRequest;
use App\Models\PickingBatch;
use App\Models\PickingBatchItem;
use Illuminate\Database\Eloquent\Builder;

trait PickingSteps
{
    protected ?PickingBatch $pickingBatch = null;

    /**
     * @Given I start picking order :orderNumber
     */
    public function iStartPickingOrder($orderNumber)
    {
        $order = $this->getCustomerInScope()->orders()
            ->where('number', $orderNumber)
            ->firstOrFail();

        $customerWarehouseId = app('user')->getCustomerWarehouseId($this->getCustomerInScope());

        $singleOrderPickingRequest = SingleOrderRequest::make([
            'order_id' => $order->id,
            'customer_id' => $order->customer->parent_id ?: $order->customer_id
        ]);

        $this->pickingBatch = app('routeOptimizer')->findOrCreatePickingBatch(
            $singleOrderPickingRequest->get('customer_id'),
            1,
            PickingBatch::TYPE_SO,
            $singleOrderPickingRequest->get('tag_id'),
            $singleOrderPickingRequest->get('tag_name'),
            $singleOrderPickingRequest->get('order_id'),
            $customerWarehouseId
        );
    }

    /**
     * @Then the picking batch asks me to pick :sku from :locationName location
     */
    public function theProductShouldBePickedFromLocation($sku, $locationName)
    {
        $pickingBatchItem = $this->pickingBatch->pickingBatchItems()
            ->whereHas('orderItem', fn (Builder $query) => $query->where('sku', $sku))
            ->firstOrFail();

        $this->assertEquals($locationName, $pickingBatchItem->location->name);
    }
}
