<?php

namespace App\Models;

use App\Traits\HasPrintables;
use App\Features\ReservePickingQuantities;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature;

/**
 * App\Models\LocationProduct
 *
 * @property int $id
 * @property int $product_id
 * @property int $location_id
 * @property int $quantity_on_hand
 * @property int $quantity_reserved_for_picking
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read Location $location
 * @property-read Product|null $product
 * @method static Builder|LocationProduct newModelQuery()
 * @method static Builder|LocationProduct newQuery()
 * @method static Builder|LocationProduct query()
 * @method static Builder|LocationProduct whereCreatedAt($value)
 * @method static Builder|LocationProduct whereDeletedAt($value)
 * @method static Builder|LocationProduct whereId($value)
 * @method static Builder|LocationProduct whereLocationId($value)
 * @method static Builder|LocationProduct whereProductId($value)
 * @method static Builder|LocationProduct whereQuantityOnHand($value)
 * @method static Builder|LocationProduct whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class LocationProduct extends Pivot
{
    use HasFactory, HasPrintables;

    protected $fillable = [
        'product_id',
        'location_id',
        'quantity_on_hand',
        'quantity_reserved_for_picking'
    ];

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id')->withTrashed();
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id')->withTrashed();
    }

    public function calculateQuantityReservedForPicking()
    {
        $quantityReservedForPicking = 0;

        if (Feature::for('instance')->active(ReservePickingQuantities::class)) {
            $pickingBatchQuery = PickingBatchItem::query()
                ->join('order_items', 'order_items.id', '=', 'picking_batch_items.order_item_id')
                ->where('picking_batch_items.location_id', $this->location_id)
                ->where('order_items.product_id', $this->product_id);

            $pickingBatchItemQuantity = $pickingBatchQuery
                ->sum('picking_batch_items.quantity');

            $quantityRemoved = $pickingBatchQuery
                ->join('tote_order_items', 'picking_batch_items.id', '=', 'tote_order_items.picking_batch_item_id')
                ->sum('tote_order_items.quantity_removed');

            $quantityReservedForPicking = $pickingBatchItemQuantity - $quantityRemoved;
        }

        $this->update([
            'quantity_reserved_for_picking' => $quantityReservedForPicking
        ]);
    }

    public function quantityByDate(Carbon $date)
    {
        $currentQuantity = $this->quantity_on_hand;
        $maxQuantity = null;

        // TODO: Don't iterate every single change - get the important ones.
        foreach ($this->inventoryChanges($date)->groupBy('formatted_date') as $changeDate => $inventoryChanges) {
            if ($changeDate == $date->toDateString()) {
                foreach ($inventoryChanges as $inventoryChange) {
                    $quantity = max($inventoryChange->previous_on_hand, $inventoryChange->new_on_hand);

                    if ($quantity > $maxQuantity) {
                        $maxQuantity = $quantity;
                    }
                }

                if ($inventoryChanges->count() > 1) {
                    $key = array_search($inventoryChanges->max('id'), array_column($inventoryChanges->toArray(), 'id'));
                    $newestQuantityByChange = $inventoryChanges[$key];
                    $currentQuantity = $newestQuantityByChange->new_on_hand;
                }
            } else {
                // TODO: What's this code for? Aren't we getting changes just for the requested date?
                foreach ($inventoryChanges as $inventoryChange) {
                    $currentQuantity -= $inventoryChange->quantity;
                }
            }
        }

        if (empty($maxQuantity)) {
            $missing = $this->inventoryChanges($date, true)->first();
            $currentQuantity = $missing ? $missing->previous_on_hand + $missing->quantity : 0;
        }

        return $maxQuantity ?? $currentQuantity;
    }

    public function inventoryChanges($date = null, $getMissing = false)
    {
        $changes = InventoryLog::where('location_id', $this->location_id)
            ->where('product_id', $this->product_id);

        if ($date && !$getMissing) {
            $changes->whereDate('updated_at', '>=', $date);
        } elseif ($date && $getMissing) {
            $changes->whereDate('updated_at', '<=', $date)->limit(1);
        }

        $changes->select(
            'inventory_logs.*',
            DB::raw('DATE_FORMAT(updated_at, "%Y-%m-%d") as formatted_date')
        );

        return $changes->orderBy('updated_at', 'DESC')->orderBy('id', 'DESC')->get();
    }
}
