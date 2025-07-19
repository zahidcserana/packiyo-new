<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasChildren;

class BillingCharge extends Model
{
    use HasChildren;

    protected $fillable = [
        'description',
        'quantity',
        'amount'
    ];

    public function billingBalance(): BelongsTo
    {
        return $this->belongsTo(BillingBalance::class);
    }
}
