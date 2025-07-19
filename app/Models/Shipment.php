<?php

namespace App\Models;

use App\Interfaces\AutomatableOperation;
use Database\Factories\ShipmentFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use \Venturecraft\Revisionable\RevisionableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable as AuditableTrait;
use App\Traits\Audits\ShipmentAudit;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\Models\Shipment
 *
 * @property int $id
 * @property int $order_id
 * @property int|null $shipping_method_id
 * @property int $processing_status
 * @property string|null $external_shipment_id
 * @property int|null $drop_point_id
 * @property int|null $user_id
 * @property string $cost
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string|null $packing_slip
 * @property Carbon|null $voided_at
 * @property string|null $external_manifest_id
 * @property-read Collection|BulkShipBatch[] $bulkShipBatch
 * @property-read int|null $bulk_ship_batch_count
 * @property-read ContactInformation|null $contactInformation
 * @property-read Order $order
 * @property-read Collection|Package[] $packages
 * @property-read int|null $packages_count
 * @property-read PrintJob|null $printJobs
 * @property-read Collection|\Venturecraft\Revisionable\Revision[] $revisionHistory
 * @property-read int|null $revision_history_count
 * @property-read Collection|ShipmentItem[] $shipmentItems
 * @property-read int|null $shipment_items_count
 * @property-read Collection|ShipmentLabel[] $shipmentLabels
 * @property-read int|null $shipment_labels_count
 * @property-read Collection|ShipmentTracking[] $shipmentTrackings
 * @property-read int|null $shipment_trackings_count
 * @property-read ShippingMethod|null $shippingMethod
 * @property-read User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Shipment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Shipment newQuery()
 * @method static Builder|Shipment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Shipment query()
 * @method static \Illuminate\Database\Eloquent\Builder|Shipment whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shipment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shipment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shipment whereDropPointId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shipment whereExternalManifestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shipment whereExternalShipmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shipment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shipment whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shipment wherePackingSlip($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shipment whereProcessingStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shipment whereShippingMethodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shipment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shipment whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shipment whereVoidedAt($value)
 * @method static Builder|Shipment withTrashed()
 * @method static Builder|Shipment withoutTrashed()
 * @mixin \Eloquent
 * @property int|null $warehouse_id
 * @property-read Collection|Audit[] $audits
 * @property-read int|null $audits_count
 * @property-read Customer|null $customer
 * @method static ShipmentFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Shipment whereWarehouseId($value)
 * @property-read Warehouse|null $warehouse
 */
class Shipment extends Model implements AuditableInterface, AutomatableOperation // TODO: Remove this interface when Crstl is merged.
{
    use SoftDeletes, CascadeSoftDeletes, RevisionableTrait, HasFactory;

    use AuditableTrait, ShipmentAudit {
        ShipmentAudit::transformAudit insteadof AuditableTrait;
    }

    const PROCESSING_STATUS_PENDING = 0;
    const PROCESSING_STATUS_IN_PROGRESS = 1;
    const PROCESSING_STATUS_SUCCESS = 2;
    const PROCESSING_STATUS_FAILED = 3;

    const STATUS_VALID = 'Valid';
    const STATUS_VOIDED = 'Voided';

    protected $cascadeDeletes = [
        'shipmentItems',
        'shipmentLabels',
        'shipmentTrackings',
    ];

    protected $fillable = [
        'order_id',
        'shipping_method_id',
        'user_id',
        'processing_status',
        'external_shipment_id',
        'drop_point_id',
        'packing_slip',
        'cost',
        'is_freight'
    ];

    protected $dates = [
        'voided_at'
    ];

    protected $casts = [
        'is_freight' => 'bool'
    ];

    /**
     * Audit configs
     */
    protected $auditStrict = true;

    protected $auditEvents = [
        'created' => 'getCreatedEventAttributes',
        'updated' => 'getUpdatedEventAttributes'
    ];

    protected $auditInclude = [];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function order()
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class)->withTrashed();
    }

    public function shipmentItems()
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function shipmentLabels()
    {
        return $this->hasMany(ShipmentLabel::class);
    }

    public function shipmentTrackings()
    {
        return $this->hasMany(ShipmentTracking::class);
    }

    public function contactInformation()
    {
        return $this->morphOne(ContactInformation::class, 'object');
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function printJobs()
    {
        return $this->morphOne(PrintJob::class, 'object')->withTrashed();
    }

    public function bulkShipBatch()
    {
        return $this->belongsToMany(BulkShipBatch::class, 'bulk_ship_batch_order');
    }

    /**
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);

    }

    public function links()
    {
        return $this->morphMany(Link::class, 'object');
    }

    public function customer()
    {
        // TODO: This should really be the shipper. (You can get it from $this->shippingBox->customer_id :O)
        return $this->hasOneThrough(Customer::class, Order::class, 'id', 'id', 'order_id', 'customer_id');
    }

    // TODO: Add the packaging materials weight.
    public function getTotalWeight(): float|int
    {
        $total = 0;

        foreach ($this->packages as $package) {
            $total += $package->getTotalWeight();
        }

        return $total;
    }

    public function getVolumeInOz(): float|int
    {
        $total = 0;

        foreach ($this->packages as $package) {
            $total += $package->getVolumeInOz();
        }

        return $total;
    }

    /**
     * @return string
     */
    public function getStatusText(): string
    {
        if ($this->voided_at) {
            return self::STATUS_VOIDED;
        }

        return self::STATUS_VALID;
    }

    /**
     * For use whenever a single tracking number needs to represent a shipment.
     */
    public function getFirstTrackingNumber(): string|null
    {
        return $this->shipmentTrackings->count()
            ? $this->shipmentTrackings->first()->tracking_number
            : null;
    }

    /**
     * Get the old/new attributes of a created event.
     *
     * @return array
     */
    protected function getCreatedEventAttributes(): array
    {
        $new = [];

        if (is_auditable($this->order)) {
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

        if (is_auditable($this->order)) {
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

    public function trackingNumbersLink()
    {
        $trackingNumbers = '';

        if (!is_null($this->shipmentTrackings)) {
            foreach ($this->shipmentTrackings as $tracking) {
                $trackingNumbers .= '<a href="' . $tracking->tracking_url . '" target="_blank" class="text-neutral-text-gray">' . $tracking->tracking_number . '</a><br/>';
            }
        }

        return $trackingNumbers;
    }

    public function trackingNumbers()
    {
        if ($this->shipmentTrackings->count() > 0) {
            $trackingNumbers = $this->shipmentTrackings->pluck('tracking_number')->toArray();

            return join(' ', $trackingNumbers);
        }

        return null;
    }

    public function isGeneric(): bool
    {
        return empty($this->shippingMethod);
    }

    public function shippingBoxNames()
    {
        return $this->packages->pluck('shippingBox.name')->toArray();
    }
}
