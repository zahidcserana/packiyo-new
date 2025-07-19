<?php

namespace App\Models;

use App\Features\NewCustomsPrice;
use App\Features\PendingOrderSlip;
use Illuminate\Database\{
    Eloquent\Builder,
    Eloquent\Collection,
    Eloquent\Model,
    Eloquent\SoftDeletes};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Pennant\Feature;
use Illuminate\Support\{Arr, Carbon};
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable as AuditableTrait;
use App\Traits\Audits\OrderItemAudit;

/**
 * App\Models\OrderItem
 *
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property float $quantity
 * @property float $quantity_shipped
 * @property int|null $component_quantity
 * @property int $quantity_returned
 * @property float $quantity_pending
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int $quantity_allocated
 * @property int $quantity_allocated_pickable
 * @property int $quantity_backordered
 * @property string $sku
 * @property string $name
 * @property float $price
 * @property float $weight
 * @property float $height
 * @property float $length
 * @property float $width
 * @property int|null $order_item_kit_id
 * @property string|null $external_id
 * @property string|null $cancelled_at
 * @property int $quantity_reshipped
 * @property string|null $ordered_at
 * @property-read Collection|OrderItem[] $kitOrderItems
 * @property-read int|null $kit_order_items_count
 * @property-read \App\Models\Order $order
 * @property-read Collection|\App\Models\PackageOrderItem[] $packageOrderItems
 * @property-read int|null $package_order_items_count
 * @property-read OrderItem|null $parentOrderItem
 * @property-read Collection|\App\Models\PickingBatchItem[] $pickingBatchItems
 * @property-read int|null $picking_batch_items_count
 * @property-read Collection|\App\Models\ToteOrderItem[] $placedToteOrderItems
 * @property-read int|null $placed_tote_order_items_count
 * @property-read \App\Models\Product $product
 * @property-read Collection|\Venturecraft\Revisionable\Revision[] $revisionHistory
 * @property-read int|null $revision_history_count
 * @property-read Collection|\App\Models\ShipmentItem[] $shipmentItems
 * @property-read int|null $shipment_items_count
 * @property-read Collection|\App\Models\ToteOrderItem[] $toteOrderItems
 * @property-read int|null $tote_order_items_count
 * @method static Builder|OrderItem newModelQuery()
 * @method static Builder|OrderItem newQuery()
 * @method static \Illuminate\Database\Query\Builder|OrderItem onlyTrashed()
 * @method static Builder|OrderItem query()
 * @method static Builder|OrderItem whereCancelledAt($value)
 * @method static Builder|OrderItem whereCreatedAt($value)
 * @method static Builder|OrderItem whereDeletedAt($value)
 * @method static Builder|OrderItem whereExternalId($value)
 * @method static Builder|OrderItem whereHeight($value)
 * @method static Builder|OrderItem whereId($value)
 * @method static Builder|OrderItem whereLength($value)
 * @method static Builder|OrderItem whereName($value)
 * @method static Builder|OrderItem whereOrderId($value)
 * @method static Builder|OrderItem whereOrderItemKitId($value)
 * @method static Builder|OrderItem whereOrderedAt($value)
 * @method static Builder|OrderItem wherePrice($value)
 * @method static Builder|OrderItem whereProductId($value)
 * @method static Builder|OrderItem whereQuantity($value)
 * @method static Builder|OrderItem whereQuantityAllocated($value)
 * @method static Builder|OrderItem whereQuantityAllocatedPickable($value)
 * @method static Builder|OrderItem whereQuantityBackordered($value)
 * @method static Builder|OrderItem whereQuantityPending($value)
 * @method static Builder|OrderItem whereQuantityReshipped($value)
 * @method static Builder|OrderItem whereQuantityReturned($value)
 * @method static Builder|OrderItem whereQuantityShipped($value)
 * @method static Builder|OrderItem whereSku($value)
 * @method static Builder|OrderItem whereUpdatedAt($value)
 * @method static Builder|OrderItem whereWeight($value)
 * @method static Builder|OrderItem whereWidth($value)
 * @method static \Illuminate\Database\Query\Builder|OrderItem withTrashed()
 * @method static \Illuminate\Database\Query\Builder|OrderItem withoutTrashed()
 * @mixin \Eloquent
 * @property-read mixed $first_product_image_source
 */

class OrderItem extends Model implements AuditableInterface
{
    use SoftDeletes, HasFactory;

    use AuditableTrait, OrderItemAudit {
        OrderItemAudit::transformAudit insteadof AuditableTrait;
    }

    protected $fillable = [
        'order_id',
        'external_id',
        'product_id',
        'quantity',
        'quantity_shipped',
        'quantity_returned',
        'quantity_pending',
        'quantity_allocated',
        'quantity_allocated_pickable',
        'quantity_backordered',
        'quantity_reshipped',
        'quantity_in_tote',
        'component_quantity',
        'sku',
        'name',
        'price',
        'height',
        'length',
        'weight',
        'width',
        'order_item_kit_id',
        'cancelled_at',
        'ordered_at',
        'tax',
        'discount'
    ];

    protected $attributes = [
        'quantity_shipped' => 0
    ];

    protected $casts = [
        'price' => 'float'
    ];

    protected $appends = [
        'first_product_image_source',
        'quantity_reshippable',
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
        'quantity_shipped',
        'sku',
        'tax',
        'discount',
        'cancelled_at'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function kitOrderItems()
    {
        return $this->hasMany(__CLASS__, 'order_item_kit_id');
    }

    public function parentOrderItem()
    {
        return $this->belongsTo(__CLASS__, 'order_item_kit_id');
    }

    public function toteOrderItems()
    {
        return $this->hasMany(ToteOrderItem::class)->withTrashed();
    }

    public function placedToteOrderItems()
    {
        return $this->hasMany(ToteOrderItem::class)->whereRaw('quantity_remaining > 0')->withTrashed();
    }

    public function tote() {
        return $this->placedToteOrderItems->first()->tote ?? null;
    }

    public function pickingBatchItems()
    {
        return $this->hasMany(PickingBatchItem::class);
    }

    public function packageOrderItems()
    {
        return $this->hasMany(PackageOrderItem::class, 'order_item_id');
    }

    public function getFirstProductImageSourceAttribute()
    {
        if ($this->product) {
            return $this->product->productImages->first()->source ?? '';
        }

        return '';
    }

    public function getQuantityReshippableAttribute()
    {
        return max(0, $this->quantity_shipped - $this->quantity_reshipped);
    }

    public function returnItems()
    {
        return $this->hasMany(ReturnItem::class)->withTrashed();
    }

    public function shipmentItems()
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function isComponent(): bool
    {
        return !empty($this->order_item_kit_id);
    }

    public function componentQuantityForKit($recalculate = false): int
    {
        $quantity = $this->component_quantity;

        if ($this->isComponent() && $this->parentOrderItem && $this->parentOrderItem->product_id) {
            if ($recalculate || !$this->component_quantity) {
                $quantity = $this->parentOrderItem->product->kitItems()
                    ->where('child_product_id', $this->product_id)
                    ->first()
                    ->pivot->quantity ?? 0;
            }
        }

        return $quantity;
    }

    public function quantityForOrderSlip(): int
    {
        if (Feature::for('instance')->active(PendingOrderSlip::class)) {
            return $this->quantity_pending;
        }

        return $this->quantity;
    }

    /**
     * Far from perfect. Still need to decide how the price should be calculated for component lines.
     */
    public function priceForCustoms(): float
    {
        $customer = $this->order->customer->is3plChild() ? $this->order->customer->parent : $this->order->customer;

        if ($customer->hasFeature(NewCustomsPrice::class)) {
            $customsPrice = $this->newCustomsPrice();
        } else {
            $customsPrice = $this->oldCustomsPrice();
        }

        return $customsPrice;
    }

    private function oldCustomsPrice(): float
    {
        return $this->isComponent() ? $this->product?->price : $this->price;
    }

    private function newCustomsPrice(): float
    {
        if ($this->isComponent()){
            return $this->unitPrice(roundUp: true);
        }

        // If the product has a customs price, use that
        $productCustomsPrice = (float) $this->product?->customs_price > 0 ? $this->product->customs_price : null;

        if ($productCustomsPrice) {
            return floatval($productCustomsPrice);
        }

        // If the product has a price, but not a customs price, use the product's price
        $productPrice = (float) $this->product?->price > 0 ? $this->product->price : null;

        if ($productPrice) {
            return floatval($productPrice);
        }

        // TODO is this correct? If we don't have the product or it's price is 0, should we return the price of the order item?
        return floatval($this->price);
    }

    public function unitPrice(bool $roundUp): float
    {
        $rounding = $roundUp ? PHP_ROUND_HALF_UP : PHP_ROUND_HALF_DOWN;
        $kitPrice = $this->parentOrderItem->priceForCustoms();

        // TODO: Mateus, let's talk about this, I couldn't quite figure out what you were doing here.
        // $eachComponentsPrice = round($kitPrice / $this->parentOrderItem->kitOrderItems()->count(), 2, $rounding);
        // return round($eachComponentsPrice / $this->componentQuantityForKit(), 2, $rounding);

        $componentQuantity = $this->parentOrderItem->kitOrderItems->sum(fn (OrderItem $item) => $item->componentQuantityForKit());

        return round($kitPrice / $componentQuantity, 2, $rounding);
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
}
