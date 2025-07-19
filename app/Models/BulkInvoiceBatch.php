<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use InvalidArgumentException;

class BulkInvoiceBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', //3pl id
        'period_start',
        'period_end',
        'csv_url', //still thinking of this
        'recalculated_from_bulk_invoice_batch_id', //not implemented TODO
        'status'
    ];

    public function bulkInvoiceBatchInvoices(): HasMany
    {
        return $this->hasMany(BulkInvoiceBatchInvoice::class);
    }

    public function invoices(): HasManyThrough
    {
        return $this->hasManyThrough(
            Invoice::class,                 // Final model you want to retrieve (Invoice)
            BulkInvoiceBatchInvoice::class,  // Intermediate model (pivot table)
            'bulk_invoice_batch_id',         // Foreign key on the pivot table (BulkInvoiceBatchInvoice)
            'id',                            // Local key on the Invoice model
            'id',                            // Local key on BulkInvoiceBatch
            'invoice_id'                     // Foreign key on the Invoice model
        );
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function getBulkInvoiceBatchStatus(): ?InvoiceStatus
    {
        //maybe needs more consideration
        return InvoiceStatus::from($this->status);
    }

    public function setBulkInvoiceBatchStatus($value): void
    {
        if ($value == null) {
            $this->status = $value;
        } elseif (in_array($value, InvoiceStatus::cases())) {
            $this->status = $value;
        } else {
            throw new InvalidArgumentException("Invalid batch invoice status $value");
        }

        $this->save();
    }
}
