<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * App\Models\BulkShipBatchOrder
 *
 * @property int $id
 * @property int $order_id
 * @property int $bulk_ship_batch_id
 * @property int $labels_merged
 * @property int|null $shipment_id
 * @property string|null $started_at
 * @property string|null $finished_at
 * @property string|null $status_message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Order|null $order
 * @property-read Shipment|null $shipment
 * @method static Builder|BulkShipBatchOrder newModelQuery()
 * @method static Builder|BulkShipBatchOrder newQuery()
 * @method static Builder|BulkShipBatchOrder query()
 * @method static Builder|BulkShipBatchOrder whereBulkShipBatchId($value)
 * @method static Builder|BulkShipBatchOrder whereCreatedAt($value)
 * @method static Builder|BulkShipBatchOrder whereFinishedAt($value)
 * @method static Builder|BulkShipBatchOrder whereId($value)
 * @method static Builder|BulkShipBatchOrder whereLabelsMerged($value)
 * @method static Builder|BulkShipBatchOrder whereOrderId($value)
 * @method static Builder|BulkShipBatchOrder whereShipmentId($value)
 * @method static Builder|BulkShipBatchOrder whereStartedAt($value)
 * @method static Builder|BulkShipBatchOrder whereStatusMessage($value)
 * @method static Builder|BulkShipBatchOrder whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BulkShipBatchOrder extends Pivot
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
