<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\RateCard
 *
 * @property int $id
 * @property string $name
 * @property string|null $monthly_cost
 * @property string|null $per_user_cost
 * @property string|null $per_purchase_order_received_cost
 * @property string|null $per_product_cost
 * @property string|null $per_shipment_cost
 * @property string|null $per_return_cost
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection|Customer[] $customers
 * @property-read int|null $customers_count
 * @property-read Collection|Invoice[] $invoice
 * @property-read int|null $invoice_count
 * @method static Builder|RateCard newModelQuery()
 * @method static Builder|RateCard newQuery()
 * @method static \Illuminate\Database\Query\Builder|RateCard onlyTrashed()
 * @method static Builder|RateCard query()
 * @method static Builder|RateCard whereCreatedAt($value)
 * @method static Builder|RateCard whereDeletedAt($value)
 * @method static Builder|RateCard whereId($value)
 * @method static Builder|RateCard whereMonthlyCost($value)
 * @method static Builder|RateCard whereName($value)
 * @method static Builder|RateCard wherePerProductCost($value)
 * @method static Builder|RateCard wherePerPurchaseOrderReceivedCost($value)
 * @method static Builder|RateCard wherePerReturnCost($value)
 * @method static Builder|RateCard wherePerShipmentCost($value)
 * @method static Builder|RateCard wherePerUserCost($value)
 * @method static Builder|RateCard whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|RateCard withTrashed()
 * @method static \Illuminate\Database\Query\Builder|RateCard withoutTrashed()
 * @mixin \Eloquent
 * @property int|null $3pl_id
 * @property-read Collection|BillingRate[] $billingRates
 * @property-read int|null $billing_rates_count
 * @method static Builder|RateCard where3plId($value)
 */
class RateCard extends Model
{
    use SoftDeletes, HasFactory;

    const SETTINGS_HIDDEN_COLUMNS_KEY = 'billings_table_hide_columns';

    public const PRIMARY_RATE_CARD_PRIORITY = 0;
    public const SECONDARY_RATE_CARD_PRIORITY = 1;

    protected $cascadeDeletes = [
        'billingRates'
    ];

    protected $fillable = [
        'name',
        'monthly_cost',
        'per_user_cost',
        'per_purchase_order_received_cost',
        'per_product_cost',
        'per_shipment_cost',
        'per_return_cost',
        '3pl_id'
    ];

    /**
     * @return BelongsToMany
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class);
    }

    /**
     * @return HasMany
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany
     */
    public function billingRates(): HasMany
    {
        return $this->hasMany(BillingRate::class);
    }

    /**
     * @return BelongsTo
     */
    public function threePL(): BelongsTo
    {
        return $this->belongsTo(Customer::class, '3pl_id');
    }
}
