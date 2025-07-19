<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BulkInvoiceBatchInvoice extends Pivot
{
    public function bulkInvoiceBatch(): BelongsTo
    {
        return $this->belongsTo(BulkInvoiceBatch::class,'batch_invoice_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class,'invoice_id');
    }
}
