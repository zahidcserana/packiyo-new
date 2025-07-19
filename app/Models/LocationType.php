<?php

namespace App\Models;

use App\Traits\Audits\LocationTypeAudit;
use Database\Factories\LocationTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

/**
 * App\Models\LocationType
 *
 * @property int $id
 * @property int $customer_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $pickable
 * @property int|null $sellable
 * @property int|null $disabled_on_picking_app
 * @property int|null $bulk_ship_pickable
 * @property-read \App\Models\Customer $customer
 * @property-read Collection|\App\Models\Location[] $locations
 * @property-read int|null $locations_count
 * @method static \Database\Factories\LocationTypeFactory factory(...$parameters)
 * @method static Builder|LocationType newModelQuery()
 * @method static Builder|LocationType newQuery()
 * @method static Builder|LocationType query()
 * @method static Builder|LocationType whereBulkShipPickable($value)
 * @method static Builder|LocationType whereCreatedAt($value)
 * @method static Builder|LocationType whereCustomerId($value)
 * @method static Builder|LocationType whereDeletedAt($value)
 * @method static Builder|LocationType whereDisabledOnPickingApp($value)
 * @method static Builder|LocationType whereId($value)
 * @method static Builder|LocationType whereName($value)
 * @method static Builder|LocationType wherePickable($value)
 * @method static Builder|LocationType whereSellable($value)
 * @method static Builder|LocationType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class LocationType extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes;

    use AuditableTrait, LocationTypeAudit {
        LocationTypeAudit::transformAudit insteadof AuditableTrait;
    }

    public const SELLABLE_NO = 0;
    public const SELLABLE_YES = 1;
    public const SELLABLE_NOT_SET = 2;

    public const PICKABLE_NO = 0;
    public const PICKABLE_YES = 1;
    public const PICKABLE_NOT_SET = 2;

    public const BULK_SHIP_PICKABLE_NO = 0;
    public const BULK_SHIP_PICKABLE_YES = 1;
    public const BULK_SHIP_PICKABLE_NOT_SET = 2;

    public const DISABLED_ON_PICKING_APP_NO = 0;
    public const DISABLED_ON_PICKING_APP_YES = 1;
    public const DISABLED_ON_PICKING_APP_NOT_SET = 2;

    protected $fillable = [
        'customer_id',
        'pickable',
        'sellable',
        'receiving',
        'name',
        'bulk_ship_pickable',
        'disabled_on_picking_app'
    ];

    protected $auditEvents = [
        'created' => 'getCreatedEventAttributes',
        'updated' => 'getUpdatedEventAttributes',
    ];

    protected $auditInclude = [
        'name',
        'pickable',
        'sellable',
        'receiving',
        'bulk_ship_pickable',
        'disabled_on_picking_app'
    ];

    public static $columnBoolean = [
        'pickable',
        'sellable',
        'receiving',
        'bulk_ship_pickable',
        'disabled_on_picking_app'
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

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function isPickable(): bool
    {
        return (bool) $this->pickable;
    }

    public function isSellable(): bool
    {
        return (bool) $this->sellable;
    }
}
