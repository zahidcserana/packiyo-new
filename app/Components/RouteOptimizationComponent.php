<?php

namespace App\Components;

use App\Features\MultiWarehouse;
use App\Http\Requests\PickingBatch\PickRequest;
use App\Jobs\ProcessPickingTask;
use App\Models\{Customer,
    Location,
    Order,
    OrderItem,
    OrderLock,
    PickingBatch,
    PickingBatchItem,
    PickingCart,
    Tag,
    Task,
    TaskType,
    Tote,
    ToteOrderItem,
    User,
    UserSetting,
    Warehouse,
    CustomerSetting};
use Carbon\Carbon;
use Illuminate\Database\{Eloquent\Collection, Eloquent\HigherOrderBuilderProxy, Eloquent\Builder};
use Illuminate\Support\{Arr, Facades\DB, Facades\Log};
use Laravel\Pennant\Feature;

class RouteOptimizationComponent extends BaseComponent
{
    public const PICKING_STRATEGY_ALPHANUMERICALLY = 'alphanumerically';
    public const PICKING_STRATEGY_MOST_INVENTORY = 'most_inventory';
    public const PICKING_STRATEGY_LEAST_INVENTORY = 'least_inventory';

    public function __construct()
    {
        $this->startLocation = 'A1';
        $this->horizontalBlockDistance = 10;

        $this->frontEndOddValue['A'] = ['number' => 1, 'block' => 1];
        $this->rearEndOddValue['A'] = ['number' => 49, 'block' => 1];
        $this->frontEndOddValue['B'] = ['number' => 49, 'block' => 1];
        $this->rearEndOddValue['B'] = ['number' => 1, 'block' => 1];

        $this->frontEndEvenValue['B'] = ['number' => 50, 'block' => 2];
        $this->rearEndEvenValue['B'] = ['number' => 2, 'block' => 2];
        $this->frontEndOddValue['C'] = ['number' => 1, 'block' => 2];
        $this->rearEndOddValue['C'] = ['number' => 49, 'block' => 2];

        $this->frontEndEvenValue['C'] = ['number' => 2, 'block' => 3];
        $this->rearEndEvenValue['C'] = ['number' => 50, 'block' => 3];
        $this->frontEndOddValue['D'] = ['number' => 49, 'block' => 3];
        $this->rearEndOddValue['D'] = ['number' => 1, 'block' => 3];

        $this->frontEndEvenValue['D'] = ['number' => 50, 'block' => 4];
        $this->rearEndEvenValue['D'] = ['number' => 2, 'block' => 4];
        $this->frontEndOddValue['E'] = ['number' => 1, 'block' => 4];
        $this->rearEndOddValue['E'] = ['number' => 49, 'block' => 4];
    }

    public function getStartLocation()
    {
        return $this->startLocation;
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    protected function addAllocatedOrderItemsFilterToQuery(Builder $query): Builder
    {
        return $query->whereDoesntHave('orderItems', function ($query) {
            $query->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('location_product')
                    ->join('locations', 'location_product.location_id', '=', 'locations.id')
                    ->whereColumn('location_product.product_id', 'order_items.product_id')
                    ->where('locations.pickable_effective', 1)
                    ->where('locations.disabled_on_picking_app_effective', 0)
                    ->groupBy('location_product.product_id')
                    ->havingRaw('SUM(location_product.quantity_on_hand) - SUM(location_product.quantity_reserved_for_picking) < order_items.quantity');
            });
        });
    }

    /**
     * @param int $customerId
     * @param string $type
     * @param int|null $tagId
     * @param string|null $tagName
     * @param int|null $orderId
     * @param bool $excludeSingleLineOrders
     * @param int|null $warehouseId
     * @return Builder[]|Collection
     */
    public function getValidOrdersToPick(int $customerId, string $type = '', int $tagId = null, ?string $tagName = '', int $orderId = null, bool $excludeSingleLineOrders = false, int $warehouseId = null)
    {
        $customersToPickFrom = Customer::withClients($customerId)->pluck('id')->toArray();

        $query = Order::whereDoesntHave('orderLock')
                ->whereIntegerInRaw('customer_id', $customersToPickFrom)
                ->where('ready_to_pick', '1')
                ->where(
                    fn($query) => $query->whereNull('disabled_on_picking_app')
                    ->orWhere('disabled_on_picking_app', 0)
                );

        if ($warehouseId && Feature::for('instance')->active(MultiWarehouse::class)) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($type == PickingBatch::TYPE_SO) {
            $query = static::addAllocatedOrderItemsFilterToQuery($query);
        } else {
            $query = $query->whereHas('orderItems');
        }

        $query = $query->withCount('orderItems')
            ->whereDoesntHave('orderItems.placedToteOrderItems')
            ->whereNull('fulfilled_at')
            ->orderBy('priority', 'desc')
            ->orderBy('ordered_at');

        if ($type == PickingBatch::TYPE_SIB) {
            $query = $query->having('order_items_count', '=', 1);
        } elseif ($type == PickingBatch::TYPE_SO) {
            $query = $query->where('id', '=', $orderId);
        } elseif ($excludeSingleLineOrders) {
            $query = $query->having('order_items_count', '>', 1);
        }

        if ($tagId) {
            $tag = Tag::find($tagId);

            if ($tag) {
                $tagName = $tag->name;
            }
        }

        if ($tagName) {
            $query = $query->whereHas('tags', function ($query) use ($tagName) {
                $query->where('name', '=', $tagName);
            })->withCount('tags');
        }

        return $query->get();
    }

    /**
     * @param int $customerId
     * @param int $quantity
     * @param string $type
     * @param int|null $tagId
     * @param string|null $tagName
     * @param int|null $orderId
     * @param int|null $warehouseId
     * @return mixed
     */
    public function findOrCreatePickingBatch(int $customerId, int $quantity , string $type, ?int $tagId, ?string $tagName, ?int $orderId, int $warehouseId = null)
    {
        $startTime = time();
        $user = auth()->user();

        Log::channel('picking')->info('User ' . $user->email . ' is trying to create a picking batch');

        $excludeSingleLineOrders = (bool) user_settings(UserSetting::USER_SETTING_EXCLUDE_SINGLE_LINE_ORDERS);

        $pickingBatch = $this->getExistingPickingBatch($user->id, $type, $orderId, $customerId);

        $this->logPickingBatchSettings($type, $tagName, $excludeSingleLineOrders, $orderId);

        if (!$pickingBatch) {
            $pickingBatch = $this->createPickingBatch($customerId, $type, $tagId, $tagName, $orderId, $excludeSingleLineOrders, $warehouseId);

            $customer = Customer::find($customerId);
            $taskType = $this->getTaskType($customer);

            Log::channel('picking')->info('Picking batch id: ' . $pickingBatch->id);

            $task = $this->createTask($pickingBatch, $taskType, $customer, $user);

            $pickingBatch = $this->processPickingTask($customerId, $quantity, $type, $tagId, $tagName, $orderId, $pickingBatch, $task, $excludeSingleLineOrders, $startTime, $user, $warehouseId);
        }

        return $pickingBatch;
    }

    /**
     * @param $customerId
     * @param $quantity
     * @param $type
     * @param $tagId
     * @param $tagName
     * @param $orderId
     * @param $pickingBatch
     * @param $task
     * @param $excludeSingleLineOrders
     * @param $startTime
     * @param $user
     * @param $warehouseId
     * @return HigherOrderBuilderProxy|mixed|null
     */
    protected function processPickingTask($customerId, $quantity, $type, $tagId, $tagName, $orderId, $pickingBatch, $task, $excludeSingleLineOrders, $startTime, $user, $warehouseId = null)
    {
        $jobDispatched = false;

        do {
            if ((time() - $startTime) > 60) {
                return null;
            }

            if ($jobDispatched) {
                $pickingBatch = $this->getExistingPickingBatch($user->id, $type, $orderId, $customerId);

                if ($pickingBatch && !$pickingBatch->status) {
                    return $pickingBatch;
                }
            } else {
                ProcessPickingTask::dispatch(
                    $customerId,
                    $quantity,
                    $type,
                    $tagId,
                    $tagName,
                    $orderId,
                    $pickingBatch,
                    $task,
                    $excludeSingleLineOrders,
                    $user,
                    $warehouseId
                );

                Log::channel('picking')->info('Picking task for batch ' . $pickingBatch->id . ' has been dispatched');

                $jobDispatched = true;
            }

            usleep(500000);
        } while (true);
    }

    public function getExistingPickingBatch(int $userId, string $type, ?int $orderId, ?int $customerId)
    {
        $query = Task::with(['taskable'])
            ->where('user_id', $userId)
            ->when($customerId !== null, function ($query) use ($customerId) {
                $query->where('customer_id', $customerId);
            })
            ->whereHasMorph('taskable', [PickingBatch::class], function ($query) use ($type, $orderId) {
                $query->where('type', $type)
                    ->whereNull('deleted_at');

                if ($orderId) {
                    $query->whereJsonContains('order_ids', $orderId);
                }
            })
            ->whereNull('completed_at')
            ->orderByDesc('created_at')
            ->first();

        return $query->taskable ?? null;
    }

    /**
     * @param int $customerId
     * @param string $type
     * @param int|null $tagId
     * @param string|null $tagName
     * @param int|null $orderId
     * @param bool|null $excludeSingleLineOrders
     * @param int|null $warehouseId
     * @return mixed
     */
    public function createPickingBatch(int $customerId, string $type, int | null $tagId, string | null $tagName, int | null $orderId, bool | null $excludeSingleLineOrders, int $warehouseId = null)
    {
        if (is_null($warehouseId)) {
            return PickingBatch::create([
                'customer_id' => $customerId,
                'type' => $type,
                'tag_id' => $tagId,
                'tag_name' => $tagName,
                'order_ids' => json_encode([$orderId]),
                'exclude_single_line_orders' => $excludeSingleLineOrders,
                'status' => 'creating'
            ]);
        }

        return PickingBatch::create([
            'customer_id' => $customerId,
            'warehouse_id' => $warehouseId,
            'type' => $type,
            'tag_id' => $tagId,
            'tag_name' => $tagName,
            'order_ids' => json_encode([$orderId]),
            'exclude_single_line_orders' => $excludeSingleLineOrders,
            'status' => 'creating'
        ]);
    }

    /**
     * @param PickingBatch $pickingBatch
     * @param Task $task
     * @param bool $forcedDelete
     * @return void
     */
    public function deletePickingBatchAndTask(PickingBatch $pickingBatch, Task $task, bool $forcedDelete = false): void
    {
        Log::channel('picking')->info('Deleting picking batch and task');

        if ($forcedDelete) {
            $pickingBatch->forceDelete();
            $task->forceDelete();

            Log::channel('picking')->info('Picking batch and task successfully (force) deleted.');
        } else {
            $pickingBatch->delete();
            $task->delete();

            Log::channel('picking')->info('Picking batch and task successfully deleted');
        }
    }

    public function processPickingBatch($quantity, $orders, User $user, $pickingBatch, $task, $warehouse)
    {
        Log::channel('picking')->info('Processing picking batch');

        $this->logPickingStrategy($warehouse);

        $quantity = $quantity ?? 1;

        if ($quantity > count($orders)) {
            $quantity = count($orders);
        }

        $pickingCart = $this->getPickingCart($warehouse, $quantity);
        $locationProductsUsed = [];
        $shortestPaths = [];
        $ordersUsed = [];
        $lockedOrders = [];

        Log::channel('picking')->info("Number of orders " . count($orders));

        /**
         * @var Order $order
         */
        foreach ($orders as $order) {
            Log::channel('picking')->info("Taking order {$order->id} {$order->number}");

            $orderItems = $order->orderItems;
            $orderShortestPaths = [];
            $orderQuantityNeeded = 0;
            $orderQuantityToTake = 0;
            $orderLock = null;

            foreach ($orderItems as $orderItem) {
                Log::channel('picking')->info("Need {$orderItem->quantity_allocated_pickable} x {$orderItem->sku}");

                if (in_array($order->id, $lockedOrders)) {
                    Log::channel('picking')->warning("Order {$order->number} is locked");
                    break;
                }

                if ($orderItem->kitOrderItems->count() > 0) {
                    Log::channel('picking')->info("Skipping kit line");
                    continue;
                }

                if (!$orderItem->product) {
                    Log::channel('picking')->warning("Won't add non-product line {$orderItem->sku} from order {$order->number} to a picking batch");
                    continue;
                }

                $quantityNeeded = $orderItem->quantity_allocated_pickable;
                $orderQuantityNeeded += $quantityNeeded;

                $locations = $orderItem->product
                    ->getSortedLocationsQuery($warehouse)
                    ->get();

                Log::channel('picking')->info("Number of valid locations - {$locations->count()}");

                foreach ($locations as $location) {
                    Log::channel('picking')->info("Checking  {$location->id} {$location->name}");

                    if (in_array($order->id, $lockedOrders)) {
                        Log::channel('picking')->warning("1 Order {$order->number} is locked");
                        break;
                    }

                    $quantityUsed = Arr::get($locationProductsUsed, $location->pivot->location_id . '.' . $location->pivot->product_id, 0);
                    $quantityToTake = min($quantityNeeded, $location->pivot->quantity_on_hand - $location->pivot->quantity_reserved_for_picking - $quantityUsed);

                    Log::channel('picking')->info("Quantity already used on picking batch {$quantityUsed}");
                    Log::channel('picking')->info("Can take {$quantityToTake}");

                    if ($quantityToTake < 1) {
                        Log::channel('picking')->warning("Not enough inventory to pick from", [
                            'customer_id'=> $user->customer_id,
                            'user_id'=> $user->id,
                            'picking_batch' => $pickingBatch->id,
                            'order_id' => $order->id,
                            'order_number' => $order->number,
                            'order_item_id' => $orderItem->id,
                            'sku' => $orderItem->sku,
                            'location_id' => $location->id,
                            'order_quantity_needed' => $orderQuantityNeeded,
                            'item_quantity_needed' => $quantityNeeded,
                            'quantity_user' => $quantityUsed,
                            'quantity_to_take' => $quantityToTake
                        ]);
                        continue;
                    }

                    if (!in_array($order->id, $ordersUsed)) {
                        $orderLock = OrderLock::firstOrCreate(
                            ['order_id' => $order->id],
                            [
                                'user_id' => $user->id,
                                'lock_type' => OrderLock::LOCK_TYPE_PICKING
                            ],
                        );

                        if (!$orderLock->wasRecentlyCreated) {
                            $lockedOrders[$order->id] = $order->id;
                            break;
                        }
                    }

                    $ordersUsed[$order->id] = $order->id;

                    $orderShortestPaths[] = [
                        'nextLocation' => $location->id,
                        'locationName' => $location->name,
                        'orderIds' => [$orderItem->order_id],
                        'orderItemId' => $orderItem->id,
                        'productId' => $orderItem->product_id,
                        'quantity' => $quantityToTake,
                        'orderNumbers' => [$orderItem->order->number]
                    ];

                    $quantityNeeded -= $quantityToTake;

                    $orderQuantityToTake += $quantityToTake;

                    Log::channel('picking')->info("Taking {$quantityToTake} from {$location->name}. Still need {$quantityNeeded}");

                    Arr::set($locationProductsUsed, $location->pivot->location_id . '.' . $location->pivot->product_id, $quantityUsed + $quantityToTake);

                    if ($quantityNeeded == 0) {
                        break;
                    }
                }
            }

            Log::channel('picking')->info("Quantity needed for whole order - {$orderQuantityNeeded}");
            Log::channel('picking')->info("Quantity taken for the whole order - {$orderQuantityToTake}");

            if ($orderQuantityToTake == $orderQuantityNeeded) {
                Log::channel('picking')->info("All lines are pickable, adding order to the picking batch");
                $shortestPaths = array_merge($shortestPaths, $orderShortestPaths);
            } else {
                Log::channel('picking')->info("Cannot pick all lines, removing order from the picking batch");
                unset($ordersUsed[$order->id]);
                if ($orderLock) {
                    $orderLock->delete();
                }
            }

            if (count($ordersUsed) == $quantity) {
                break;
            }
        }

        if (empty($shortestPaths)) {
            $this->deletePickingBatchAndTask($pickingBatch, $task);
        } else {
            $pickingBatch = $this->store($pickingBatch, $shortestPaths);

            if ($pickingCart) {
                $this->assignCartToPickingBatch($pickingCart, $pickingBatch);
            }
        }

        return $pickingBatch;
    }

    public function getTaskType(Customer $customer)
    {
        return TaskType::where('customer_id', $customer->id)->where('type', TaskType::TYPE_PICKING)->first();
    }

    public function createTask(PickingBatch $pickingBatch, TaskType $taskType, Customer $customer, $user): Task
    {
        $task = new Task();
        $task->taskable()->associate($pickingBatch);
        $task->user_id = $user->id;
        $task->customer_id = $customer->id;
        $task->task_type_id = $taskType->id;
        $task->notes = '';
        $task->save();

        Log::channel('picking')->info('New task created for picking batch with id: ' . $pickingBatch->id);

        return $task;
    }

    public function store($pickingBatch, $shortestPaths)
    {
        $orderIds = [];

        $shortestPaths = Arr::sort($shortestPaths, function ($shortestPath) {
            return $shortestPath['locationName'];
        });

        foreach ($shortestPaths as $shortestPath) {
            $location = Location::find($shortestPath['nextLocation']);
            $orderItem = OrderItem::find($shortestPath['orderItemId']);

            PickingBatchItem::create([
                'picking_batch_id' => $pickingBatch->id,
                'order_item_id' => $orderItem->id,
                'location_id' => $location->id,
                'quantity' => $shortestPath['quantity']
            ]);

            if (!in_array($orderItem->order_id, $orderIds)) {
                $orderIds[] = $orderItem->order_id;
            }
        }

        $pickingBatch->status = '';
        $pickingBatch->order_ids = json_encode($orderIds);
        $pickingBatch->save();

        return $pickingBatch;
    }

    public function pick(PickRequest $request)
    {
        $input = $request->validated();
        $orders = $input['orders'];

        foreach ($orders as $order) {
            $pickingBatchItem = PickingBatchItem::find($order['picking_batch_item_id']);
            $quantityLeft = $pickingBatchItem->quantity - $pickingBatchItem->quantity_picked;

            if ($quantityLeft) {
                $order['quantity'] = min($order['quantity'], $quantityLeft);
                $pickingBatchItem->quantity_picked += $order['quantity'];
                $pickingBatchItem->save();

                $toteOrderItem = ToteOrderItem::firstOrNew([
                    'picking_batch_item_id' => $pickingBatchItem->id,
                    'order_item_id' => $pickingBatchItem->order_item_id,
                    'location_id' => $input['location_id'],
                    'tote_id' => $input['tote_id'],
                ]);

                $toteOrderItem->quantity += $order['quantity'];
                $toteOrderItem->picked_at = Carbon::now();
                $toteOrderItem->user_id = auth()->user()->id;
                $toteOrderItem->save();

                Tote::auditCustomEvent(
                    $toteOrderItem->tote,
                    'picked',
                    __('Picked <em>:quantity x :sku</em> for order <em>:order</em>', [
                            'quantity' => $order['quantity'],
                            'sku' => $toteOrderItem->orderItem->sku,
                            'order' => $toteOrderItem->orderItem->order->number
                        ]
                    )
                );

                Order::auditCustomEvent(
                    $toteOrderItem,
                    'picked',
                    __('Picked <em>:quantity x :sku</em> to tote <em>:tote</em> from location <em>:location</em>', [
                            'quantity' => $order['quantity'],
                            'sku' => $toteOrderItem->orderItem->sku,
                            'tote' => Tote::find($input['tote_id'])->name,
                            'location' => Location::find($input['location_id'])->name
                        ]
                    )
                );

                Log::channel('picking')->info("{$order['quantity']} of SKU: {$toteOrderItem->orderItem->sku} from order number {$toteOrderItem->orderItem->order->number} picked into tote {$toteOrderItem->tote->name}");
            }
        }

        if (PickingBatchItem::where('picking_batch_id', '=', $input['picking_batch_id'])->sum(DB::raw('quantity - quantity_picked')) === 0) {
            $task = Task::where('taskable_id', $input['picking_batch_id'])
                ->where('taskable_type', PickingBatch::class)
                ->first();
            $task->completed_at = Carbon::now();
            $task->save();

            $pickingBatch = PickingBatch::with('pickingBatchItems.orderItem')->find($input['picking_batch_id']);
            $orderIds = [];

            foreach ($pickingBatch->pickingBatchItems as $pickingBatchItem) {
                if (!in_array($pickingBatchItem->orderItem->order_id, $orderIds)) {
                    $orderIds[] = $pickingBatchItem->orderItem->order_id;
                }
            }

            OrderLock::whereIntegerInRaw('order_id', $orderIds)->delete();
        }

        return collect([]);
    }

    public function reformPaths($orders, $paths)
    {
        $totalPathDistance = 0;

        foreach ($paths as $key => $path) {
            $orderIds = [];
            foreach ($orders as $order) {
                $item = OrderItem::where('order_id', $order->id)->where('product_id', $path['productId'])->first();

                if ($item) {
                    $orderIds[] = $order->id;
                }
            }

            $orderIdsStr = implode(', ', $orderIds);

            $paths[$key]['orderIds'] = $orderIds;
            $paths[$key]['orderIdsStr'] = $orderIdsStr;

            $totalPathDistance += $path['pathDistance'];
        }

        return compact("paths", "totalPathDistance");
    }

    public function getAllShortestPaths($startLocation, $items)
    {
        $currentLocation = $startLocation;
        $itemsArr = [];
        $paths = [];

        foreach ($items as $key => $item) {
            $itemsArr[$item->product_id] = $item->product->locations->pluck('name')->toArray();
        }

        while (count($itemsArr) > 0) {

            $shortestPathDetails = $this->getShortestPath($startLocation, $itemsArr);

            $productId = $shortestPathDetails['product_id'];
            $startLocation = $shortestPathDetails['location'];

            $paths[] = [
                'productId' => $productId,
                'currentLocation' => $currentLocation,
                'nextLocation' => $shortestPathDetails['location'],
                'pathDistance' => $shortestPathDetails['minimum']
            ];

            $currentLocation = $startLocation;

            unset($itemsArr[$productId]);
        }

        return $paths;
    }

    private function getShortestPath($startLocation, $items)
    {
        $details['minimum'] = 0;

        $temp = 0;

        foreach ($items as $key => $locations) {
            foreach ($locations as $locKey => $location) {
                $distance = $this->getDistance($startLocation, $location);

                if ($temp == 0 && $locKey == 0) {
                    $details['minimum'] = $distance;
                    $details['product_id'] = $key;
                    $details['location'] = $location;
                }

                if ($distance < $details['minimum']) {
                    $details['minimum'] = $distance;
                    $details['product_id'] = $key;
                    $details['location'] = $location;
                }

                $temp++;
            }
        }

        return $details;
    }

    private function getDistance($location1, $location2)
    {
        $sameAisle = $this->checkSameAisle($location1, $location2);

        if ($sameAisle) {
            $distance = $this->getDistanceOfSameAisle($location1, $location2);
        } else {
            $distance = $this->getDistanceOfDifferentAisles($location1, $location2);
        }

        return $distance;
    }

    private function getDistanceOfSameAisle($location1, $location2)
    {
        $distance = 0;

        $number1 = $this->getNumericPart($location1);

        $number2 = $this->getNumericPart($location2);

        $greater = $this->getGreater($number1, $number2);

        if ($this->isEven($number1) && $this->isEven($number2)) {
            $distance = abs(($number1 - $number2)) / 2;
        } else if ($this->isOdd($number1) && $this->isOdd($number2)) {
            $distance = abs(($number1 - $number2)) / 2;
        } else if ($this->isEven($greater)) {
            $distance = ceil(abs(($number1 - $number2)) / 2) - 1;
        } else {
            $distance = ceil(abs(($number1 - $number2)) / 2);
        }

        return $distance;
    }

    private function getDistanceOfDifferentAisles($location1, $location2)
    {
        $letter1 = $this->getLetterPart($location1);

        $letter2 = $this->getLetterPart($location2);

        $number1 = $this->getNumericPart($location1);

        $number2 = $this->getNumericPart($location2);

        if ($this->isEven($number1)) {
            $frontEndValue1 = $this->frontEndEvenValue[$letter1]['number'];
            $rearEndValue1 = $this->rearEndEvenValue[$letter1]['number'];
            $block1 = $this->rearEndEvenValue[$letter1]['block'];
        } else {
            $frontEndValue1 = $this->frontEndOddValue[$letter1]['number'];
            $rearEndValue1 = $this->rearEndOddValue[$letter1]['number'];
            $block1 = $this->rearEndOddValue[$letter1]['block'];
        }

        $frontRearDetails1 = $this->getFrontNearDetails($frontEndValue1, $rearEndValue1, $number1);

        if ($this->isEven($number2)) {
            $frontEndValue2 = $this->frontEndEvenValue[$letter2]['number'];
            $rearEndValue2 = $this->rearEndEvenValue[$letter2]['number'];
            $block2 = $this->rearEndEvenValue[$letter2]['block'];
        } else {
            $frontEndValue2 = $this->frontEndOddValue[$letter2]['number'];
            $rearEndValue2 = $this->rearEndOddValue[$letter2]['number'];
            $block2 = $this->rearEndOddValue[$letter2]['block'];
        }

        $frontRearDetails2 = $this->getFrontNearDetails($frontEndValue2, $rearEndValue2, $number2);

        $locationsDistance = $this->getDistanceFromSameEnd($frontRearDetails1, $frontRearDetails2);

        $blockDistance = $this->getBlockDistance($block1, $block2);

        $distance = $locationsDistance + $blockDistance;

        return $distance;
    }

    private function getFrontNearDetails($frontEndValue, $rearEndValue, $number)
    {
        if (abs($frontEndValue - $number) <= abs($rearEndValue - $number)) {
            $distanceFromNearestEnd = (abs($frontEndValue - $number)) / 2;
            $distanceFromOtherEnd = (abs($rearEndValue - $number)) / 2;

            $nearestEnd = 'Front';
        } else {
            $distanceFromNearestEnd = (abs($rearEndValue - $number)) / 2;
            $distanceFromOtherEnd = (abs($frontEndValue - $number)) / 2;

            $nearestEnd = 'Rear';
        }

        return compact("nearestEnd", "distanceFromNearestEnd", "distanceFromOtherEnd");
    }

    private function getDistanceFromSameEnd($frontRearDetails1, $frontRearDetails2)
    {
        if ($frontRearDetails1['distanceFromNearestEnd'] <= $frontRearDetails2['distanceFromNearestEnd']) {
            $nearsetEnd = $frontRearDetails1['nearestEnd'];
            $nearsetEndDistance = $frontRearDetails1['distanceFromNearestEnd'];

            $otherLocationDistanceFromThisEnd = $frontRearDetails2['nearestEnd'] == $nearsetEnd ? $frontRearDetails2['distanceFromNearestEnd'] : $frontRearDetails2['distanceFromOtherEnd'];

            $totalDistance = $nearsetEndDistance + $otherLocationDistanceFromThisEnd;
        } else {
            $nearsetEnd = $frontRearDetails2['nearestEnd'];
            $nearsetEndDistance = $frontRearDetails2['distanceFromNearestEnd'];

            $otherLocationDistanceFromThisEnd = $frontRearDetails1['nearestEnd'] == $nearsetEnd ? $frontRearDetails1['distanceFromNearestEnd'] : $frontRearDetails1['distanceFromOtherEnd'];

            $totalDistance = $nearsetEndDistance + $otherLocationDistanceFromThisEnd;
        }

        return $totalDistance;
    }

    private function getBlockDistance($block1, $block2)
    {
        if (abs($block1 - $block2) == 0) {
            return 1;
        }

        return abs($block1 - $block2) * $this->horizontalBlockDistance;
    }

    private function checkSameAisle($location1, $location2)
    {
        $letter1 = $this->getLetterPart($location1);

        $letter2 = $this->getLetterPart($location2);

        return $letter1 == $letter2;
    }

    private function getNumericPart($location)
    {
        return (int)preg_replace('/[^0-9]/', '', $location);
    }

    private function getLetterPart($location)
    {
        return preg_replace('/[^a-zA-Z]/', '', $location);
    }

    private function isEven($number)
    {
        return $number % 2 == 0;
    }

    private function isOdd($number)
    {
        return $number % 2 != 0;
    }

    private function getGreater($number1, $number2)
    {
        if ($number1 == $number2) {
            return false;
        }

        if ($number1 > $number2) {
            return $number1;
        }

        return $number2;
    }

    /**
     * @param Warehouse $warehouse
     * @param $quantity
     * @return PickingCart|null
     */
    private function getPickingCart(Warehouse $warehouse, $quantity): ?PickingCart
    {
        $pickingCarts = PickingCart::where('number_of_totes', '>=', $quantity)
            ->where('warehouse_id', $warehouse->id)
            ->orderBy('number_of_totes', 'asc')
            ->get();

        if (!is_null($pickingCarts)) {
            foreach ($pickingCarts as $pickingCart) {
                /** @var PickingCart $pickingCart */
                if ($pickingCart->totes_count > $quantity) {
                    Log::channel('picking')->info("Picking into cart {$pickingCart->name}");
                    return $pickingCart;
                }
            }
        }

        return null;
    }

    /**
     * @param PickingCart $pickingCart
     * @param PickingBatch $pickingBatch
     * @return bool
     */
    private function assignCartToPickingBatch(PickingCart $pickingCart, PickingBatch $pickingBatch): bool
    {
        try {
            $pickingBatch->picking_cart_id = $pickingCart->id;
            $pickingBatch->save();

            return true;
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            Log::channel('picking')->error("Picking batch {$pickingBatch->id} could not be assigned to picking cart {$pickingCart->name}", $exception->getTrace());
            return false;
        }
    }

    /**
     * @param string $type
     * @param string|null $tagName
     * @param bool $excludeSingleLineOrders
     * @param int|null $orderId
     * @return void
     */
    private function logPickingBatchSettings(string $type, ?string $tagName, bool $excludeSingleLineOrders, ?int $orderId): void
    {
        match ($type) {
            PickingBatch::TYPE_SO => Log::channel('picking')->info('Picking batch is of single order type'),
            PickingBatch::TYPE_SIB => Log::channel('picking')->info('Picking batch is of single item batch type'),
            PickingBatch::TYPE_MIB => Log::channel('picking')->info('Picking batch is of multi item batch type')
        };

        Log::channel('picking')->info('Picking batch ' . ($excludeSingleLineOrders ? 'skips' : 'includes') . ' single line orders');

        if ($orderId) {
            Log::channel('picking')->info('Picking batch created for order: ' . Order::find($orderId)->number);
        }

        if ($tagName) {
            Log::channel('picking')->info('Picking batch has the following tags: ' . $tagName);
        }
    }

    /**
     * @param $warehouse
     * @return void
     */
    private function logPickingStrategy($warehouse): void
    {
        $customerParentId = $warehouse->customer->parent_id;
        $pickingRouteStrategy = customer_settings($warehouse->customer_id, CustomerSetting::CUSTOMER_SETTING_PICKING_ROUTE_STRATEGY);

        if (is_null($pickingRouteStrategy) && $customerParentId) {
            $pickingRouteStrategy = customer_settings($customerParentId, CustomerSetting::CUSTOMER_SETTING_PICKING_ROUTE_STRATEGY);
        }

        match ($pickingRouteStrategy) {
            RouteOptimizationComponent::PICKING_STRATEGY_MOST_INVENTORY => Log::channel('picking')->info('Picking strategy: Most inventory'),
            RouteOptimizationComponent::PICKING_STRATEGY_LEAST_INVENTORY => Log::channel('picking')->info('Picking strategy: Least inventory'),
            default => Log::channel('picking')->info('Picking strategy: Alphanumerically')
        };
    }
}
