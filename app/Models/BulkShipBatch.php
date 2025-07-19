<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\{Auth, DB, Log};
use Illuminate\Database\Eloquent\{Builder,
    Collection,
    Model,
    Relations\BelongsTo,
    Relations\BelongsToMany,
    Relations\MorphMany,
    Relations\HasMany,
    Relations\HasOne};
use PDO;

/**
 * App\Models\BulkShipBatch
 *
 * @property int $id
 * @property int $customer_id
 * @property string $batch_key
 * @property int $total_orders
 * @property int $total_items
 * @property Carbon|null $shipped_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $label
 * @property int|null $printed_user_id
 * @property string $printed_at
 * @property int|null $packed_user_id
 * @property string $packed_at
 * @property int $shipped
 * @property int|null $warehouse_id
 * @property-read Collection|Order[] $orders
 * @property-read int|null $orders_count
 * @property-read User|null $packedUser
 * @property-read User|null $printedUser
 * @property-read Product|null $product
 * @property-read Warehouse|null $warehouse
 * @method static Builder|BulkShipBatch newModelQuery()
 * @method static Builder|BulkShipBatch newQuery()
 * @method static Builder|BulkShipBatch query()
 * @method static Builder|BulkShipBatch whereBatchKey($value)
 * @method static Builder|BulkShipBatch whereCreatedAt($value)
 * @method static Builder|BulkShipBatch whereCustomerId($value)
 * @method static Builder|BulkShipBatch whereId($value)
 * @method static Builder|BulkShipBatch whereLabel($value)
 * @method static Builder|BulkShipBatch wherePackedAt($value)
 * @method static Builder|BulkShipBatch wherePackedUserId($value)
 * @method static Builder|BulkShipBatch wherePrintedAt($value)
 * @method static Builder|BulkShipBatch wherePrintedUserId($value)
 * @method static Builder|BulkShipBatch whereShipped($value)
 * @method static Builder|BulkShipBatch whereShippedAt($value)
 * @method static Builder|BulkShipBatch whereTotalItems($value)
 * @method static Builder|BulkShipBatch whereTotalOrders($value)
 * @method static Builder|BulkShipBatch whereUpdatedAt($value)
 * @method static Builder|BulkShipBatch whereWarehouseId($value)
 * @mixin \Eloquent
 */
class BulkShipBatch extends Model
{
    protected $guarded = [];

    protected $dates = ['shipped_at'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function bulkShipBatchOrders(): HasMany
    {
        return $this->hasMany(BulkShipBatchOrder::class);
    }

    public function firstBulkShipBatchOrder(): HasOne
    {
        return $this->hasOne(BulkShipBatchOrder::class, 'bulk_ship_batch_id')
            ->oldest()
            ->limit(1);
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class)->withPivot(
            'id',
            'started_at',
            'finished_at',
            'shipment_id',
            'labels_merged',
            'status_message',
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function printedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_user_id');
    }

    public function packedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'packed_user_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function lockTask(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function lock()
    {
        $bulkShipBatch = $this;

        DB::transaction(static function () use ($bulkShipBatch) {
            if (is_null($bulkShipBatch->lock_task_id)) {
                $bulkShipBatch->update(['lock_task_id' => $bulkShipBatch->firstOrCreateTask()->id]);
            }

            $bulkShipBatch->lockOrders();
        }, 10);

        return $this;
    }

    public function firstOrCreateTask()
    {
        $taskType = TaskType::firstOrCreate([
            'name' => 'Shipping Batch',
            'customer_id' => app('user')->getSessionCustomer()->id,
        ]);

        return $this->tasks()->create([
            'user_id' => Auth::id(),
            'customer_id' => app('user')->getSessionCustomer()->id,
            'task_type_id' => $taskType->id,
        ]);
    }

    public function unlock()
    {
        $this->tasks()
            ->where('id', $this->lock_task_id)
            ->update(['completed_at' => now()]);

        $this->update([
            'lock_task_id' => null,
        ]);

        $this->unlockOrders();

        return $this;
    }

    private function lockOrders()
    {
        if (!$this->in_progress) {
            $this->syncSuggestedOrders();
        } else {
            $orderIds = $this->orders()
                ->wherePivotNull('shipment_id')
                ->whereNotNull('fulfilled_at')
                ->get()
                ->modelKeys();

            $this->orders()->detach($orderIds);
        }

        $orderLocks = $this->orders()->doesntHave('orderLock')->get()->modelKeys();

        $orderLocks = array_map(static function($orderId) {
            return [
                'order_id' => $orderId,
                'user_id' => Auth::id(),
                'lock_type' => OrderLock::LOCK_TYPE_BULK_SHIP,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $orderLocks);

        // workaround for "Prepared statement contains too many placeholders" error with big batches.
        DB::connection()->getPdo()->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        DB::table('order_locks')->insert($orderLocks);
        DB::connection()->getPdo()->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public function unlockOrders()
    {
        OrderLock::whereIntegerInRaw('order_id', $this->orders->modelKeys())->delete();
    }

    public function syncSuggestedOrders()
    {
        Log::channel('bulkshipping')->info('Syncing suggested orders with the batch.');

        $this->orders()->sync(
            $this->suggestedOrdersQuery()->get('id')->modelKeys()
        );
    }

    public function suggestedOrdersQuery()
    {
        // exclude in progress
        return Order::where('batch_key', $this->batch_key)
            ->where('ready_to_ship', 1)
            ->whereDoesntHave('orderLock', function($query) {
                return $query->where('user_id', '<>', Auth::id());
            })
            ->whereDoesntHave('bulkShipBatch', fn (Builder $query) => $query->where('bulk_ship_batches.id', '<>', $this->id))
            ->take(
                min(config('bulk_ship.batch_order_limit'), $this->total_orders)
            );
    }

    public function ordersShippedAmount(): int
    {
        return $this->orders()->select('order_id')->count('order_id');
    }
}
