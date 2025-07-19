<?php

use App\Components\UserComponent;
use App\Models\BulkShipBatch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Printer;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Http\Requests\Order\StoreBatchRequest as OrderStoreBatchRequest;
use Behat\Gherkin\Node\TableNode;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Laravel\Pennant\Feature;

/**
 * Behat steps to navigate a web app using a web driver browser.
 */
trait BulkShippingSteps
{
    /**
     * @Given the bulk ship batch order limit is :limit
     */
    public function theBulkShipBatchOrderLimitIs(int $limit): void
    {
        Config::set('bulk_ship.batch_order_limit', $limit);
    }

    /**
     * @Given the minimum similar orders for bulk shipping is :minSimilarOrders
     */
    public function theMinimumSimilarOrdersForBulkShippingIs(int $minSimilarOrders): void
    {
        Config::set('bulk_ship.min_similar_orders', $minSimilarOrders);
    }

    /**
     * @Then the logged user starts to ship the latest bulk ship batch
     */
    public function theLoggedUserStartsToShipTheLatestBulkShipBatch(): void
    {
        $bulkShipBatch = BulkShipBatch::query()
            ->whereCustomerId($this->getWarehouseInScope()->customer_id)
            ->orderBy('id', 'desc')
            ->first();
        $customer = $this->getCustomerInScope();

        Session::put(UserComponent::SESSION_CUSTOMER_KEY, $customer->id);

        $this->get(route('bulk_shipping.shipping', [$bulkShipBatch->id]));
    }

    /**
     * @Then the order :orderNumber should have the same batch key from the order :orderNumber2
     */
    public function theOrderShouldHaveTheSameBatchKeyFromTheOrder($orderNumber, $orderNumber2): void
    {
        $customer = $this->getCustomerInScope();

        $order1 = $customer->orders()->where('number', $orderNumber)->firstOrFail();
        $order2 = $customer->orders()->where('number', $orderNumber2)->firstOrFail();

        $this->assertEquals($order1->batch_key, $order2->batch_key);
    }

    /**
     * @Then the order :orderNumber should have a different batch key from the order :orderNumber2
     */
    public function theOrderShouldHaveADifferentBatchKeyFromTheOrder($orderNumber, $orderNumber2): void
    {
        $customer = $this->getCustomerInScope();

        $order1 = $customer->orders()->where('number', $orderNumber)->firstOrFail();
        $order2 = $customer->orders()->where('number', $orderNumber2)->firstOrFail();

        $this->assertNotEquals($order1->batch_key, $order2->batch_key);
    }

    /**
     * @Then the order :orderNumber should have a batch key
     */
    public function theOrderShouldHaveABatchKey(string $orderNumber): void
    {
        $order = Order::whereNumber($orderNumber)->firstOrFail();

        $this->assertNotNull($order->batch_key);
    }

    /**
     * @Then the logged user ships the latest bulk ship batch with limit :limit
     */
    public function theLoggedUserShipsTheLatestBulkShipBatchWithLimit(int $limit): void
    {
        $bulkShipBatch = BulkShipBatch::whereCustomerId($this->getWarehouseInScope()->customer_id)
            ->latest()
            ->first();
        $customer = $this->getCustomerInScope();
        $location = $this->locationInScope;
        $printer = Printer::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $orders = $bulkShipBatch->orders;
        $firstOrderItem = $orders[0]->orderItems->first();

        $data = [
            'bulk_ship_batch_id' => $bulkShipBatch->id,
            'batch_filter' => [
                'shipping_carrier' => '',
                'shipping_method' => '',
            ],
            'order_shipping_method_mappings' => '[]',
            'customer_id' => $customer->id,
            'mapping_type' => 'regular',
            'total_unpacked_items' => 0,
            'total_unpacked_weight' => 0,
            // use map instead of hardcoded
            'shipping_method_id' => $orders->mapWithKeys(fn ($order) => [$order->id => 'generic'])->toArray(),
            'length' => 1,
            'width' => 1,
            'height' => 1,
            'weight' => 2,
            'order_items' => [
                "0_{$firstOrderItem->id}__{$location->id}_0_1" => [
                    'quantity' => 1,
                    'order_item_id' => $firstOrderItem->id,
                    'location_id' => $location->id,
                    'tote_id' => null,
                ],
            ],
            'batch_shipping_limit' => $limit,
            'print_packing_slip' => '',
            'custom-package-length' => '',
            'custom-package-width' => '',
            'custom-package-height' => '',
            'shipping_contact_information' => [
                'name' => 'John Doe',
                'company_name' => 'A',
                'address' => '123 Main St',
                'address2' => 'Apt. 12',
                'zip' => '12345',
                'city' => 'New York',
                'state' => 'New York',
                'country_id' => 1,
                'email' => 'john@doe.com',
                'phone' => '',
                'company_number' => 2,
            ],
            'printer_id' => $printer->id,
            'serial_number' => '',
        ];
        $data['packing_state'] = json_encode([[
            'items' => [
                [
                    'orderItem' => (string) $firstOrderItem->id,
                    'location' => "$location->id",
                    'tote' => 0,
                    'serialNumber' => '',
                    'packedParentKey' => '',
                ]
            ],
            'weight' => 2,
            'box' => $customer->shippingBoxes()->first()->id,
            '_length' => '1',
            'width' => '1',
            'height' => '1',
            'custom' => 0,
        ]]);

        // Make the POST request
        $this->post(route('bulk_shipping.ship', [$bulkShipBatch->id]), $data);
    }


    /**
     * @Then the total order amount must be :amount
     */
    public function theTotalOrderAmountMustBe($amount): void
    {
        $bulkShipBatch = BulkShipBatch::whereCustomerId($this->getWarehouseInScope()->customer_id)
            ->first();

        $this->assertEquals($bulkShipBatch->orders()->count(), $amount);
    }


    /**
     * @Then I should have :amount bulk ship batches with the same key
     */
    public function iShouldHaveBulkShipBatchesWithKey(int $amount): void
    {
        $bulkShipBatch = BulkShipBatch::query()
            ->select('batch_key', DB::raw('count(*) as total'))
            ->whereCustomerId($this->getWarehouseInScope()->customer_id)
            ->groupBy('batch_key')
            ->first();

        $this->assertEquals($amount, $bulkShipBatch->total);
    }

    /**
     * @Then these orders should belong to bulk ship batch number :number
     */
    public function theseOrdersShouldBelongToBulkShipBatchNumber(int $number, TableNode $ordersNumbers): void
    {
        $ordersNumber = $ordersNumbers->getRow(0);

        $customer = $this->getCustomerInScope();
        $ordersIds = $customer->orders()
            ->whereIn('number', $ordersNumber)
            ->get(['id'])
            ->pluck('id')
            ->toArray();

        $bulkShipBatch = BulkShipBatch::query()
            ->with('orders')
            ->whereCustomerId($this->getWarehouseInScope()->customer_id)
            ->offset($number - 1)
            ->first();

        $this->assertEquals($ordersIds, $bulkShipBatch->orders->pluck('id')->toArray());
    }

    /**
     * @Given the customer adds :quantity of SKU :sku to these orders
     */
    public function theCustomerAddsOfSKUToTheseOrders($quantity, $sku, TableNode $ordersNumbers): void
    {
        $ordersNumber = $ordersNumbers->getRow(0);

        $customer = $this->getCustomerInScope();
        $product = Product::where(['customer_id' => $customer->id, 'sku' => $sku])->firstOrFail();

        $orders = $customer->orders()
            ->whereIn('number', $ordersNumber)
            ->get();

        foreach ($orders as $order) {
            App::make(\App\Components\OrderComponent::class)->updateOrderItems($order, [
                [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ],
            ]);
        }
    }

    /**
     * @Given the order items for the SKU :sku are cancelled for these orders
     */
    public function theOrderItemsForTheSKUAreCancelledForTheseOrders($sku, TableNode $ordersNumbers): void
    {
        $ordersNumber = $ordersNumbers->getRow(0);

        $customer = $this->getCustomerInScope();
        $product = Product::where(['customer_id' => $customer->id, 'sku' => $sku])->firstOrFail();

        $orders = $customer->orders()
            ->whereIn('number', $ordersNumber)
            ->get();

        foreach ($orders as $order) {
            $orderItem = $order->orderItems()
                ->where('sku', $product->sku)->firstOrFail();

            App::make(\App\Components\OrderComponent::class)->cancelOrderItem($orderItem);
        }
    }
}
