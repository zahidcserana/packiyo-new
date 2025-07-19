<?php

namespace App\Models;

use App\Traits\{Audits\ToteAudit, HasBarcodeTrait, HasPrintables, HasUniqueIdentifierSuggestionTrait};
use Illuminate\Database\{Eloquent\Builder, Eloquent\Collection, Eloquent\Model, Eloquent\SoftDeletes};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

/**
 * App\Models\Tote
 *
 * @property int $id
 * @property int $warehouse_id
 * @property int|null $order_id
 * @property int|null $picking_cart_id
 * @property string $name
 * @property string $barcode
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @method static bool|null forceDelete()
 * @method static Builder|Tote newModelQuery()
 * @method static Builder|Tote newQuery()
 * @method static \Illuminate\Database\Query\Builder|Tote onlyTrashed()
 * @method static Builder|Tote query()
 * @method static bool|null restore()
 * @method static Builder|Tote whereId($value)
 * @method static Builder|Tote whereBarcode($value)
 * @method static Builder|Tote whereName($value)
 * @method static Builder|Tote whereWarehouseId($value)
 * @method static Builder|Tote whereOrderId($value)
 * @method static Builder|Tote wherePickingCartId($value)
 * @method static Builder|Tote whereCreatedAt($value)
 * @method static Builder|Tote whereUpdatedAt($value)
 * @method static Builder|Tote whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Tote withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Tote withoutTrashed()
 * @property-read Collection|ToteOrderItem[] $toteOrderItems
 * @property-read int|null $tote_order_items_count
 * @property-read Warehouse $warehouse
 * @property-read Order|null $order
 * @property-read PickingCart|null $pickingCart
 * @mixin \Eloquent
 */

class Tote extends Model implements AuditableInterface
{
    use SoftDeletes, HasBarcodeTrait, HasUniqueIdentifierSuggestionTrait, HasPrintables, HasFactory;

    use AuditableTrait, ToteAudit {
        ToteAudit::transformAudit insteadof AuditableTrait;
    }

    public static $uniqueIdentifierColumn = 'name';
    public static $uniqueIdentifierReferenceColumn = 'warehouse_id';

    protected $fillable = [
        'warehouse_id',
        'order_id', // TODO: Why is this here?
        'picking_cart_id',
        'name',
        'barcode'
    ];

    protected $auditEvents = [
        'created' => 'getCreatedEventAttributes',
        'updated' => 'getUpdatedEventAttributes',
        'deleted' => 'getDeletedEventAttributes',
    ];

    protected $auditInclude = [
        'name',
        'barcode'
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

    /**
     * Get the old/new attributes of a deleted event.
     *
     * @return array
     */
    protected function getDeletedEventAttributes(): array
    {
        $old = [];

        foreach ($this->attributes as $attribute => $value) {
            if ($this->isAttributeAuditable($attribute)) {
                $old[$attribute] = $value;
            }
        }

        return [
            $old,
            [],
        ];
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class)->withTrashed();
    }

    public function customer()
    {
        return $this->hasOneThrough(Customer::class, Warehouse::class, 'id', 'id', 'warehouse_id', 'customer_id');
    }

    // TODO: But really, why is this here?
    public function order()
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    public function pickingCart()
    {
        return $this->belongsTo(PickingCart::class)->withTrashed();
    }

    public function toteOrderItems()
    {
        return $this->hasMany(ToteOrderItem::class)->withTrashed();
    }

    public function placedToteOrderItems()
    {
        return $this->hasMany(ToteOrderItem::class)->whereRaw('quantity_remaining > 0')->withTrashed();
    }

    public function delete()
    {
        if ($this->placedToteOrderItems()->exists()) {
            return false;
        }

        return parent::delete();
    }
}
