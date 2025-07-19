<?php

namespace App\Models;

use App\Traits\Audits\PurchaseOrderItemAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\PurchaseOrderItem
 *
 * @property int $id
 * @property int $purchase_order_id
 * @property int $product_id
 * @property int $location_id
 * @property float $quantity
 * @property float $quantity_received
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Product $product
 * @property-read PurchaseOrder $purchaseOrder
 * @method static bool|null forceDelete()
 * @method static Builder|PurchaseOrderItem newModelQuery()
 * @method static Builder|PurchaseOrderItem newQuery()
 * @method static \Illuminate\Database\Query\Builder|PurchaseOrderItem onlyTrashed()
 * @method static Builder|PurchaseOrderItem query()
 * @method static bool|null restore()
 * @method static Builder|PurchaseOrderItem whereCreatedAt($value)
 * @method static Builder|PurchaseOrderItem whereDeletedAt($value)
 * @method static Builder|PurchaseOrderItem whereId($value)
 * @method static Builder|PurchaseOrderItem whereProductId($value)
 * @method static Builder|PurchaseOrderItem wherePurchaseOrderId($value)
 * @method static Builder|PurchaseOrderItem whereQuantity($value)
 * @method static Builder|PurchaseOrderItem whereQuantityReceived($value)
 * @method static Builder|PurchaseOrderItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|PurchaseOrderItem withTrashed()
 * @method static \Illuminate\Database\Query\Builder|PurchaseOrderItem withoutTrashed()
 * @mixin \Eloquent
 * @property int $quantity_pending
 * @method static Builder|PurchaseOrderItem whereQuantityPending($value)
 * @property int $quantity_rejected
 * @method static Builder|PurchaseOrderItem whereQuantityRejected($value)
 * @property int $quantity_sell_ahead
 * @method static Builder|PurchaseOrderItem whereQuantityAllocatedSellAhead($value)
 * @property string|null $external_id
 * @property-read Location|null $location
 * @method static Builder|PurchaseOrderItem whereExternalId($value)
 * @method static Builder|PurchaseOrderItem whereLocationId($value)
 */
class PurchaseOrderItem extends Model implements AuditableInterface
{
    use SoftDeletes;

    use AuditableTrait, PurchaseOrderItemAudit {
        PurchaseOrderItemAudit::transformAudit insteadof AuditableTrait;
    }

    protected $fillable = [
        'purchase_order_id',
        'external_id',
        'product_id',
        'quantity',
        'quantity_received',
        'quantity_pending',
        'quantity_sell_ahead'
    ];

    /**
     * Audit configs
     */
    protected $auditStrict = true;

    protected $auditEvents = [
        'created' => 'getCreatedEventAttributes',
        'updated' => 'getUpdatedEventAttributes'
    ];

    protected $auditInclude = [
        'quantity',
        'quantity_received',
        'quantity_pending',
        'quantity_sell_ahead'
    ];

    /**
     * Get the old/new attributes of a created event.
     *
     * @return array
     */
    protected function getCreatedEventAttributes(): array
    {
        $new = [];

        if (is_auditable($this->purchaseOrder)) {
            foreach ($this->attributes as $attribute => $value) {
                if ($this->isAttributeAuditable($attribute)) {
                    $new[$attribute] = $value;
                }
            }
        }

        return [
            [],
            $new,
        ];
    }

    /**
     * Get the old/new attributes of a created event.
     *
     * @return array
     */
    public function getUpdatedEventAttributes(): array
    {
        $old = [];
        $new = [];

        if (is_auditable($this->purchaseOrder)) {
            foreach ($this->getDirty() as $attribute => $value) {
                if ($this->isAttributeAuditable($attribute)) {
                    $old[$attribute] = Arr::get($this->original, $attribute);
                    $new[$attribute] = Arr::get($this->attributes, $attribute);
                }
            }
        }

        return [
            $old,
            $new,
        ];
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class)->withTrashed();
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function location()
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }
}
