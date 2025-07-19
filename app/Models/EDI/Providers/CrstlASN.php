<?php

namespace App\Models\EDI\Providers;

use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrstlASN extends Model
{
    use SoftDeletes;

    const ASN_STATUS_VALID = 'valid';  // Unverified.
    const ASN_STATUS_INVALID = 'invalid';
    const LABELS_STATUS_GENERATING = 'generating';
    const LABELS_STATUS_UNSUPPORTED = 'unsupported';

    protected $table = 'crstl_asns';

    protected $fillable = [
        'external_shipment_id',
        'request_labels_after_ms',
        'shipping_labels_status',
        'asn_status'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class)->withTrashed();
    }

    public function packingLabels(): HasMany
    {
        return $this->hasMany(CrstlPackingLabel::class, foreignKey: 'asn_id');
    }

    public function isValid(): bool
    {
        // Deliberately not using ASN_STATUS_VALID until verified.
        return $this->asn_status != static::ASN_STATUS_INVALID;
    }

    public function areLabelsGenerating(): bool
    {
        return $this->shipping_labels_status == static::LABELS_STATUS_GENERATING;
    }

    public function areLabelsSupported(): bool
    {
        return $this->shipping_labels_status != static::LABELS_STATUS_UNSUPPORTED;
    }
}
