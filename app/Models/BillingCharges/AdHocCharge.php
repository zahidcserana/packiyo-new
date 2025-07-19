<?php

namespace App\Models\BillingCharges;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BillingCharge;
use App\Models\BillingRate;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasParent;

class AdHocCharge extends BillingCharge
{
    use HasFactory, HasParent;

    public function billingRate(): BelongsTo
    {
        return $this->belongsTo(BillingRate::class)->withTrashed();
    }
}
