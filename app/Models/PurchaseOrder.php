<?php

namespace App\Models;

use App\Interfaces\AutomatableOperation;
use App\Interfaces\ItemQuantities;
use App\Traits\Audits\PurchaseOrderAudit;
use App\Traits\HasUniqueIdentifierSuggestionTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable as AuditableTrait;

/**
 * App\Models\PurchaseOrder
 *
 * @property int $id
 * @property int $customer_id
 * @property int $warehouse_id
 * @property int|null $supplier_id
 * @property string $number
 * @property Carbon|null $ordered_at
 * @property Carbon|null $expected_at
 * @property Carbon|null $delivered_at
 * @property string|null $notes
 * @property int $priority
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Customer $customer
 * @property-read Collection|InventoryLog[] $inventoryLogSources
 * @property-read int|null $inventory_log_sources_count
 * @property-read Collection|PurchaseOrderItem[] $purchaseOrderLines
 * @property-read int|null $purchase_order_lines_count
 * @property-read Supplier $supplier
 * @property-read Warehouse $warehouse
 * @method static bool|null forceDelete()
 * @method static Builder|PurchaseOrder newModelQuery()
 * @method static Builder|PurchaseOrder newQuery()
 * @method static \Illuminate\Database\Query\Builder|PurchaseOrder onlyTrashed()
 * @method static Builder|PurchaseOrder query()
 * @method static bool|null restore()
 * @method static Builder|PurchaseOrder whereCreatedAt($value)
 * @method static Builder|PurchaseOrder whereCustomerId($value)
 * @method static Builder|PurchaseOrder whereDeletedAt($value)
 * @method static Builder|PurchaseOrder whereDeliveredAt($value)
 * @method static Builder|PurchaseOrder whereExpectedAt($value)
 * @method static Builder|PurchaseOrder whereId($value)
 * @method static Builder|PurchaseOrder whereNotes($value)
 * @method static Builder|PurchaseOrder whereNumber($value)
 * @method static Builder|PurchaseOrder whereOrderedAt($value)
 * @method static Builder|PurchaseOrder wherePriority($value)
 * @method static Builder|PurchaseOrder whereSupplierId($value)
 * @method static Builder|PurchaseOrder whereUpdatedAt($value)
 * @method static Builder|PurchaseOrder whereWarehouseId($value)
 * @method static \Illuminate\Database\Query\Builder|PurchaseOrder withTrashed()
 * @method static \Illuminate\Database\Query\Builder|PurchaseOrder withoutTrashed()
 * @mixin \Eloquent
 * @property int $purchase_order_status_id
 * @property-read Collection|PurchaseOrderItem[] $purchaseOrderItems
 * @property-read int|null $purchase_order_items_count
 * @property-read PurchaseOrderStatus $purchaseOrderStatus
 * @property-read Collection|Task[] $tasks
 * @property-read int|null $tasks_count
 * @method static Builder|PurchaseOrder wherePurchaseOrderStatusId($value)
 * @property string|null $tracking_number
 * @property string|null $tracking_url
 * @method static Builder|PurchaseOrder whereTrackingNumber($value)
 * @method static Builder|PurchaseOrder whereTrackingUrl($value)
 * @property string|null $received_at
 * @method static Builder|PurchaseOrder whereReceivedAt($value)
 * @property string|null $closed_at
 * @method static Builder|PurchaseOrder whereClosedAt($value)
 * @property string|null $external_id
 * @property int|null $order_id
 * @property-read Collection|RejectedPurchaseOrderItem[] $rejectedItems
 * @property-read int|null $rejected_items_count
 * @property-read Collection|Tag[] $tags
 * @property-read int|null $tags_count
 * @method static Builder|PurchaseOrder whereExternalId($value)
 * @method static Builder|PurchaseOrder whereOrderId($value)
 * @property-read Order|null $order
 */
class PurchaseOrder extends Model implements AuditableInterface, AutomatableOperation, ItemQuantities
{
    use SoftDeletes, HasUniqueIdentifierSuggestionTrait;

    use AuditableTrait, PurchaseOrderAudit {
        PurchaseOrderAudit::transformAudit insteadof AuditableTrait;
    }

    public const PO_PREFIX = 'PO-';
    public static $uniqueIdentifierColumn = 'number';
    public static $uniqueIdentifierReferenceColumn = 'customer_id';
    public const STATUS_CLOSED = 'Closed';
    public const STATUS_PENDING = 'Pending';

    public const PURCHASE_ORDER_STATUSES = [
        'pending' => self::STATUS_PENDING,
        'closed' => self::STATUS_CLOSED
    ];

    protected $fillable = [
        'customer_id',
        'external_id',
        'warehouse_id',
        'supplier_id',
        'order_id',
        'number',
        'ordered_at',
        'expected_at',
        'delivered_at',
        'notes',
        'priority',
        'purchase_order_status_id',
        'tracking_number',
        'tracking_url'
    ];

    protected $dates = [
        'ordered_at',
        'expected_at',
        'delivered_at',
        'received_at',
        'closed_at'
    ];

    protected $auditEvents = [
        'created' => 'getCreatedEventAttributes',
        'updated' => 'getUpdatedEventAttributes'
    ];

    protected $auditInclude = [
        'warehouse_id',
        'supplier_id',
        'number',
        'notes',
        'priority',
        'tracking_number',
        'tracking_url',
        'ordered_at',
        'expected_at',
    ];

    public static $columnBoolean = [
        'priority'
    ];

    /**
     * Get the old/new attributes of a created event.
     *
     * @return array
     */
    public function getCreatedEventAttributes(): array
    {
        $new = [];

        foreach ($this->attributes as $attribute => $value) {
            if ($this->isAttributeAuditable($attribute)) {
                $new[$attribute] = $value;
            }
        }

        return [[], $new];
    }

    /**
     * Get the old/new attributes of an updated event.
     *
     * @return array
     */
    protected function getUpdatedEventAttributes(): array
    {
        $old = [];
        $new = [];

        foreach ($this->getDirty() as $attribute => $value) {
            if ($this->isAttributeAuditable($attribute)) {
                $old[$attribute] = Arr::get($this->original, $attribute);
                $new[$attribute] = Arr::get($this->attributes, $attribute);
            }
        }

        return [$old, $new];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class)->withTrashed();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function order()
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function items(): Collection
    {
        return $this->purchaseOrderItems;
    }

    public function inventoryLogSources()
    {
        return $this->morphMany(InventoryLog::class, 'source');
    }

    public function tasks()
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function purchaseOrderStatus()
    {
        return $this->belongsTo(PurchaseOrderStatus::class);
    }

    public function rejectedItems()
    {
        return $this->hasManyThrough(RejectedPurchaseOrderItem::class, PurchaseOrderItem::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * @return string
     */
    public function getStatusText(): string
    {
        if ($this->closed_at) {
            return self::STATUS_CLOSED;
        }

        return $this->purchaseOrderStatus->name ?? self::STATUS_PENDING;
    }

    public function purchaseOrderQuantity() {
        $data = [];
        $data['ordered'] = 0;
        $data['received'] = 0;

        foreach ($this->purchaseOrderItems as $purchaseOrderItem) {
            $data['ordered'] += $purchaseOrderItem->quantity;
            $data['received'] += $purchaseOrderItem->quantity_received;
        }

        return $data;
    }

    public function getCustomMessage(): ?string
    {
        if ($this->auditEvent === 'po-item-received') {
            return __(':quantity of SKU :sku received.', [
                'quantity' => $this->auditCustomNew['quantity_received'] - $this->auditCustomOld['quantity_received'],
                'sku' => $this->auditCustomNew['sku']
            ]);
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isTransferOrder(): bool
    {
        return $this->order()->exists();
    }
}
