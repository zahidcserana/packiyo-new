<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount'
    ];

    protected $attributes = [
        'amount' => 0.0
    ];

    public function threePL(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function billingCharges(): HasMany
    {
        return $this->hasMany(BillingCharge::class);
    }

    public function debit(BillingCharge ...$charges): void
    {
        $amount = 0;

        foreach ($charges as $charge) {
            $amount += $charge->amount;
        }

        $this->decrement('amount', $amount);
    }
}
