<?php

namespace App\Components;

use App\Features\MultiWarehouse;
use App\Models\BulkShipBatch;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;

class BulkShipComponent extends BaseComponent
{
    private int $minSimilarOrders;
    private int $batchOrderLimit;
    private const BULKSHIPPING_LOG_CHANNEL = 'bulkshipping';

    public function __construct()
    {
        $this->minSimilarOrders = config('bulk_ship.min_similar_orders');
        $this->batchOrderLimit = config('bulk_ship.batch_order_limit');
    }

    public function syncBatchOrders(Collection $customers): void
    {
        $customerIds = $customers->pluck('id')->toArray();

        $warehouseIds = [];

        if (Feature::for('instance')->active(MultiWarehouse::class)) {
            $warehouseIds = auth()->user()->warehouses->pluck('id')->toArray();
        }

        $this->log('Syncing batch orders for customers.', ['Customers IDs' => $customerIds]);

        Order::with([
            'orderItems.toteOrderItems',
            'orderItems.pickingBatchItems',
            'orderItems.product',
            'mappedShippingMethod'
        ])->whereNull('batch_key')
            ->where('ready_to_ship', 1)
            ->whereIn('customer_id', $customerIds)
            ->when(!empty($warehouseIds), function (Builder $builder) use ($warehouseIds) {
                $builder->whereIn('warehouse_id', $warehouseIds);
            })
            ->eachById(function (Order $order) {
                $order
                    ->regenerateBatchKey()
                    ->saveQuietly();
            });

        $this->log('Recalculating bulk ship batches for customers.');

        $batches = Order::whereNotNull('batch_key')
            ->select([
                'customer_id',
                'batch_key',
                'warehouse_id',
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('JSON_ARRAYAGG(id) as order_ids')
            ])
            ->whereIn('customer_id', $customerIds)
            ->when(!empty($warehouseIds), function (Builder $builder) use ($warehouseIds) {
                $builder->whereIn('warehouse_id', $warehouseIds);
            })
            ->whereDoesntHave('orderLock', fn(Builder $query) => $query->where('user_id', '<>', Auth::id()))
            ->whereDoesntHave('bulkShipBatch', fn(Builder $query) => $query->where('in_progress', 1))
            ->groupBy('batch_key')
            ->having('orders_count', '>=', $this->minSimilarOrders);

        $updatedOrCreatedBatchOrdersIds = [];

        foreach ($batches->cursor() as $batch) {
            $this->log("Recalculating bulk ship batch with key: $batch->batch_key", [
                'Customer ID' => $batch->customer_id,
                'Found Batch\'s matching Orders IDs' => $batch->order_ids,
                'Batch Key' => $batch->batch_key,
            ]);

            $itemCount = 0;
            $batchKeyItems = explode(',', $batch->batch_key);

            foreach ($batchKeyItems as $batchKeyItem) {
                $splitBatchKeyItem = explode(':', $batchKeyItem);
                $itemCount += (int) $splitBatchKeyItem[1];
            }

            $limitedBatchOrderIds = array_slice(
                json_decode($batch->order_ids),
                0,
                $this->batchOrderLimit
            );

            $this->log('Batch\'s matching Orders IDs after limiting.', [
                'Batch Order Limit' => $this->batchOrderLimit,
                'Batch\'s matching Orders IDs' => $limitedBatchOrderIds
            ]);

            $batchTotalAttributes = [
                'total_orders' => count($limitedBatchOrderIds),
                'total_items' => $itemCount,
            ];

            $bulkShipBatch = BulkShipBatch::whereNull('shipped_at')
                ->where('in_progress', 0)
                ->where('batch_key', $batch->batch_key)
                ->when(!empty($warehouseIds), fn(Builder $query) => $query->where('warehouse_id', $warehouseIds))
                ->first();

            if (!$bulkShipBatch?->update($batchTotalAttributes)) {
                $bulkShipBatch = BulkShipBatch::create(
                    array_merge($batchTotalAttributes, [
                        'batch_key' => $batch->batch_key,
                        'customer_id' => $batch->customer_id,
                        'warehouse_id' => $batch->warehouse_id
                    ])
                );
            }

            $updatedOrCreatedBatchOrdersIds[] = $bulkShipBatch->id;
        }

        BulkShipBatch::whereNull('shipped_at')
            ->where('in_progress', 0)
            ->whereNotIn('id', $updatedOrCreatedBatchOrdersIds)
            ->whereDoesntHave('orders')
            ->delete();
    }

    private function getBatchKeysAndOrders(?Order $order = null): Collection
    {
        $whereOrderIdClause = '';
        $havingClause = 'HAVING count >= '.$this->minSimilarOrders;

        if ($order) {
            $whereOrderIdClause = 'AND id = '.$order->id;
            $havingClause = '';
        }

        DB::statement('SET SESSION group_concat_max_len=1000000');
        $batchOrders = DB::select(/** @lang MySQL */ '
            WITH
                filtered_order_items AS (
                    SELECT oi.product_id,
                        oi.quantity_allocated,
                        oi.order_id,
                        toi.id AS tote_order_item_id,
                        NOT ISNULL(toi.id) AS has_tote,
                        pbi.id AS picking_batch_item_id,
                        NOT ISNULL(pbi.id) AS has_picking_batch
                    FROM products p
                        LEFT JOIN order_items oi
                            ON p.id = oi.product_id
                        LEFT JOIN tote_order_items toi
                            ON oi.id = toi.order_item_id AND toi.deleted_at IS NULL
                        LEFT JOIN picking_batch_items pbi
                            ON oi.id = pbi.order_item_id AND pbi.deleted_at IS NULL
                    WHERE p.has_serial_number = 0
                        AND oi.cancelled_at IS NULL
                ), filtered_orders AS (
                    SELECT o.id,
                        o.number,
                        GROUP_CONCAT(foi.product_id, ":", foi.quantity_allocated ORDER BY foi.product_id) AS batch_key, warehouse_id
                    FROM orders o
                        LEFT JOIN filtered_order_items foi ON o.id = foi.order_id
                    WHERE quantity_pending_sum > 0
                        AND quantity_allocated_sum > 0
                        AND ready_to_ship = 1
                        AND shipping_method_id IS NOT NULL
                        '.$whereOrderIdClause.'
                    GROUP BY o.id
                    HAVING SUM(foi.has_tote) = 0
                        AND SUM(foi.has_picking_batch) = 0
                )
            SELECT batch_key,
                COUNT(*) as count,
                GROUP_CONCAT(id ORDER BY 1) as order_ids
            FROM filtered_orders
            GROUP BY batch_key, warehouse_id
            ' . $havingClause
        );

        return collect($batchOrders);
    }

    private function log($message, $context = []): void
    {
        Log::channel(self::BULKSHIPPING_LOG_CHANNEL)->info($message, $context);
    }
}
