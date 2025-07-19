<?php

namespace App\Models;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\PickingBatch
 *
 * @property int $id
 * @property int $customer_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $picking_cart_id
 * @property string|null $type
 * @property string|null $status
 * @property int|null $warehouse_id
 * @property-read Customer $customer
 * @property-read Collection|PickingBatchItem[] $pickingBatchItems
 * @property-read int|null $picking_batch_items_count
 * @property-read Collection|PickingBatchItem[] $pickingBatchItemsWithTrashed
 * @property-read int|null $picking_batch_items_with_trashed_count
 * @property-read PickingCart|null $pickingCart
 * @property-read Collection|Task[] $tasks
 * @property-read int|null $tasks_count
 * @property-read Warehouse|null $warehouse
 * @method static Builder|PickingBatch newModelQuery()
 * @method static Builder|PickingBatch newQuery()
 * @method static \Illuminate\Database\Query\Builder|PickingBatch onlyTrashed()
 * @method static Builder|PickingBatch query()
 * @method static Builder|PickingBatch whereCreatedAt($value)
 * @method static Builder|PickingBatch whereCustomerId($value)
 * @method static Builder|PickingBatch whereDeletedAt($value)
 * @method static Builder|PickingBatch whereId($value)
 * @method static Builder|PickingBatch wherePickingCartId($value)
 * @method static Builder|PickingBatch whereStatus($value)
 * @method static Builder|PickingBatch whereType($value)
 * @method static Builder|PickingBatch whereUpdatedAt($value)
 * @method static Builder|PickingBatch whereWarehouseId($value)
 * @method static \Illuminate\Database\Query\Builder|PickingBatch withTrashed()
 * @method static \Illuminate\Database\Query\Builder|PickingBatch withoutTrashed()
 * @mixin \Eloquent
 */
class PickingBatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'warehouse_id',
        'picking_cart_id',
        'status',
        'type',
        'tag_id',
        'tag_name',
        'order_ids',
        'exclude_single_line_orders'
    ];

    public const TYPE_SO = 'so';
    public const TYPE_SIB = 'sib';
    public const TYPE_MIB = 'mib';

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function pickingBatchItems()
    {
        return $this->hasMany(PickingBatchItem::class);
    }

    public function pickingBatchItemsWithTrashed()
    {
        return $this->hasMany(PickingBatchItem::class)->withTrashed();
    }

    public function pickingBatchItemsNotFinished()
    {
        return $this->pickingBatchItems()->whereColumn('quantity', '!=', 'quantity_picked');
    }

    public function tasks()
    {
        return $this->morphMany(Task::class, 'taskable')->withTrashed();
    }
    public function pickingCart()
    {
        return $this->belongsTo(PickingCart::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function getTotalBatchTime($inSeconds = false): null|string
    {
        if (!$this->tasks) {
            return null;
        }

        $completedTask = $this->tasks->sortByDesc(function ($task) {
            return $task->completed_at ?? $task->updated_at;
        })->first();

        return user_start_end_date_diff($this->created_at, $completedTask->completed_at ?? $completedTask->updated_at, $inSeconds) ?? null;
    }

    public function getTimePerPick(): null|string
    {
        $totalQuantityPicked = $this->pickingBatchItemsWithTrashed->sum('quantity_picked');

        if ($totalQuantityPicked == 0) {
            return null;
        }

        $perPickInSeconds = round($this->getTotalBatchTime(true) / $totalQuantityPicked);
        $interval = CarbonInterval::seconds($perPickInSeconds)->cascade();

        return sprintf('%sm %ss', $interval->toArray()['minutes'], $interval->toArray()['seconds']);
    }
}
