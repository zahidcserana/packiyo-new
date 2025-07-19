<?php

namespace App\Models\BillingCharges;

use App\Models\Automation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BillingCharge;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Parental\HasParent;

class ShippingBoxCharge extends BillingCharge
{
    use HasFactory, HasParent;

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
