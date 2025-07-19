<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Venturecraft\Revisionable\RevisionableTrait;

/**
 * App\Models\Invoice
 *
 * @property int $id
 * @property int|null $customer_id
 * @property string $period_start
 * @property Carbon $period_end
 * @property string|null $due_date
 * @property string|null $amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property Carbon|null $calculated_at
 * @property int $is_finalized
 * @property string|null $invoice_number
 * @property string|null $csv_url
 * @property-read Collection|InvoiceLineItem[] $invoiceLineItems
 * @property-read int|null $invoice_line_items_count
 * @property-read RateCard|null $rateCard
 * @property-read Customer|null $customer
 * @property-read Collection|\Venturecraft\Revisionable\Revision[] $revisionHistory
 * @property-read int|null $revision_history_count
 * @method static Builder|Invoice newModelQuery()
 * @method static Builder|Invoice newQuery()
 * @method static \Illuminate\Database\Query\Builder|Invoice onlyTrashed()
 * @method static Builder|Invoice query()
 * @method static Builder|Invoice whereAmount($value)
 * @method static Builder|Invoice whereInvoiceNumber($value)
 * @method static Builder|Invoice whereRateCardId($value)
 * @method static Builder|Invoice whereCalculatedAt($value)
 * @method static Builder|Invoice whereCreatedAt($value)
 * @method static Builder|Invoice whereCsvUrl($value)
 * @method static Builder|Invoice whereCustomerId($value)
 * @method static Builder|Invoice whereDeletedAt($value)
 * @method static Builder|Invoice whereDueDate($value)
 * @method static Builder|Invoice whereId($value)
 * @method static Builder|Invoice whereIsFinalized($value)
 * @method static Builder|Invoice wherePeriodEnd($value)
 * @method static Builder|Invoice wherePeriodStart($value)
 * @method static Builder|Invoice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Invoice withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Invoice withoutTrashed()
 * @mixin \Eloquent
 */
class Invoice extends Model
{
    use SoftDeletes, CascadeSoftDeletes, RevisionableTrait, HasFactory;

    public const MIGRATION_DEFAULT_TEMPLATE_NAME = 'EU_VAT_STANDARD';
    public const PDF_TEMPLATES = [
        [
            'file_name' => 'EU_VAT_STANDARD',
            'title' => 'EU vat standard'
        ],
        [
            'file_name' => 'US_STANDARD',
            'title' => 'US standard'
        ]
    ];

    protected $cascadeDeletes = [
        'invoiceLineItems'
    ];

    protected $dates = [
        'period_start',
        'period_end',
        'calculated_at'
    ];

    protected $fillable = [
        'customer_id', // TODO: Remove from $fillable.
        'period_start',
        'period_end',
        'due_date',
        'amount',
        'is_finalized',
        'invoice_number',
        'csv_url',
        'recalculated_from_invoice_id',
        'csv_url',
        'status'
    ];

    protected $attributes = [
        'status' => InvoiceStatus::PENDING_STATUS
    ];

    protected $casts = [
        'status' => InvoiceStatus::class
    ];

    public function invoiceLineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function recalculatedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'recalculated_from_invoice_id');
    }

    public function referringInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'recalculated_from_invoice_id');
    }

    public function rateCards(): BelongsToMany
    {
        return $this->belongsToMany(RateCard::class);
    }

    public function primaryRateCard(): ?RateCard
    {
        return $this->rateCards()->first();
    }

    public function secondaryRateCard(): ?RateCard
    {
        return $this->rateCards()->skip(1)->first();
    }

    public function getInvoiceStatus(): ?InvoiceStatus
    {
        // TODO: Considering checking if pending state. Need extra service to set status after the fact.
        if (empty($this->status) || $this->status == InvoiceStatus::PENDING_STATUS) {

            $currentDate = Carbon::now();

            $oneDayAgo = $currentDate->copy()->subDay();

            if (is_null($this->amount)) {
                if ($this->created_at->lt($oneDayAgo)) {
                    return InvoiceStatus::FAILED_STATUS;
                } elseif ($this->created_at->gt($oneDayAgo) && $this->created_at->lte($currentDate)) {
                    return InvoiceStatus::PENDING_STATUS;
                }
            } else {
                return InvoiceStatus::DONE_STATUS;
            }
        }

        return $this->status;
    }

    public function setInvoiceStatus($value): void
    {
        if ($value == null) {
            $this->status = $value;
        } elseif (in_array($value, InvoiceStatus::cases())) {
            $this->status = $value;
        } else {
            throw new InvalidArgumentException("Invalid invoice status $value");
        }

        $this->save();
    }

    public function bulkInvoiceBatch(): BelongsToMany
    {
        return $this->belongsToMany(BulkInvoiceBatch::class);
    }
}
