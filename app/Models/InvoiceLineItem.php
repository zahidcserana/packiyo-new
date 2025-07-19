<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\InvoiceLineItem
 *
 * @property int $id
 * @property int $invoice_id
 * @property int $billing_rate_id
 * @property string|null $description
 * @property string $quantity
 * @property string $charge_per_unit
 * @property string $total_charge
 * @property string $period_end
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $purchase_order_item_id
 * @property int|null $purchase_order_id
 * @property int|null $return_item_id
 * @property int|null $package_id
 * @property int|null $package_item_id
 * @property int|null $location_type_id
 * @property int|null $shipment_id
 * @property-read Invoice $invoice
 * @property-read BillingRate $billingRate
 * @property-read Package|null $package
 * @property-read PurchaseOrder|null $purchaseOrder
 * @property-read PurchaseOrderItem|null $purchaseOrderItem
 * @property-read ReturnItem|null $returnItem
 * @property-read Shipment|null $shipment
 * @method static Builder|InvoiceLineItem newModelQuery()
 * @method static Builder|InvoiceLineItem newQuery()
 * @method static \Illuminate\Database\Query\Builder|InvoiceLineItem onlyTrashed()
 * @method static Builder|InvoiceLineItem query()
 * @method static Builder|InvoiceLineItem whereInvoiceId($value)
 * @method static Builder|InvoiceLineItem whereBillingRateId($value)
 * @method static Builder|InvoiceLineItem whereCreatedAt($value)
 * @method static Builder|InvoiceLineItem whereDeletedAt($value)
 * @method static Builder|InvoiceLineItem whereDescription($value)
 * @method static Builder|InvoiceLineItem whereId($value)
 * @method static Builder|InvoiceLineItem whereLocationTypeId($value)
 * @method static Builder|InvoiceLineItem wherePackageId($value)
 * @method static Builder|InvoiceLineItem wherePackageItemId($value)
 * @method static Builder|InvoiceLineItem wherePeriodEnd($value)
 * @method static Builder|InvoiceLineItem wherePurchaseOrderId($value)
 * @method static Builder|InvoiceLineItem wherePurchaseOrderItemId($value)
 * @method static Builder|InvoiceLineItem whereQuantity($value)
 * @method static Builder|InvoiceLineItem whereReturnItemId($value)
 * @method static Builder|InvoiceLineItem whereShipmentId($value)
 * @method static Builder|InvoiceLineItem whereTotalPrice($value)
 * @method static Builder|InvoiceLineItem whereUnitPrice($value)
 * @method static Builder|InvoiceLineItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|InvoiceLineItem withTrashed()
 * @method static \Illuminate\Database\Query\Builder|InvoiceLineItem withoutTrashed()
 * @mixin \Eloquent
 */
class InvoiceLineItem extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'invoice_id', // TODO: Remove from $fillable.
        'billing_rate_id', // TODO: Remove from $fillable.
        'description',
        'quantity',
        'charge_per_unit',
        'total_charge',
        'due_date',
        'period_end',
        'package_id', // TODO: Remove from $fillable.
        'package_item_id', // TODO: Remove from $fillable.
        'shipment_id', // TODO: Remove from $fillable.
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class)->withTrashed();
    }

    public function billingRate(): BelongsTo
    {
        return $this->belongsTo(BillingRate::class)->withTrashed();
    }

    public function packageItem(): BelongsTo
    {
        return $this->belongsTo(PackageOrderItem::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class)->withTrashed();
    }
}
