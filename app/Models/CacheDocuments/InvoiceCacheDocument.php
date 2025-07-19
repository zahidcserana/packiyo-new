<?php

namespace App\Models\CacheDocuments;

use App\Models\Invoice;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class InvoiceCacheDocument extends Model
{

    use SoftDeletes;

    protected $connection = 'mongodb';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'period_start',
        'period_end',
        'invoice_number',
        'customer_id',
        'billing_rates',
        'valid_at' //Timestamp to confirm all documents need it for calculation were confirmed
    ];

    public static function build(Invoice $invoice, array $billingRate): static
    {
        return static::make([
            'id' => $invoice->id, //maybe we updated it to invoice_id, need opinion
            'period_start' => $invoice->period_start->toDateString(),
            'period_end' => $invoice->period_end->toDateString(),
            'invoice_number' => $invoice->invoice_number,
            'customer_id' => $invoice->customer_id,
            'billing_rates' => $billingRate,
        ]);

    }

    public function getBillingRates()
    {
        return $this->billing_rates;
    }
}
