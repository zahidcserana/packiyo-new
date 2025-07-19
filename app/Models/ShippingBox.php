<?php

namespace App\Models;

use App\Traits\HasBarcodeTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingBox extends Model
{
    use HasFactory, SoftDeletes, HasBarcodeTrait;

    public const TYPE_PARCEL = 'Parcel';
    public const TYPE_ENVELOPE = 'Envelope';
    public const TYPE_PALLET = 'Pallet';
    public const TYPE_POLY_BAG = 'Poly Bag';
    public const TYPE_MAILER = 'Mailer';
    public const TYPE_CARDBOARD = 'Cardboard';
    public const TYPE_PAPER = 'Paper';
    public const TYPE_PLASTIC = 'Plastic';
    public const TYPE_OTHER = 'Other';

    public const TYPES = [
        'parcel' => self::TYPE_PARCEL,
        'envelope' => self::TYPE_ENVELOPE,
        'pallet' => self::TYPE_PALLET,
        'poly_bag' => self::TYPE_POLY_BAG,
        'mailer' => self::TYPE_MAILER,
        'cardboard' => self::TYPE_CARDBOARD,
        'paper' => self::TYPE_PAPER,
        'plastic' => self::TYPE_PLASTIC,
        '' => self::TYPE_OTHER,
    ];

    protected $fillable = [
        'customer_id',
        'name',
        'type',
        'weight',
        'length',
        'barcode',
        'width',
        'height',
        'height_locked',
        'length_locked',
        'width_locked',
        'weight_locked',
        'cost'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    /**
     * @return float
     */
    public function getCost(): float
    {
        return $this->cost ?? 0.0;
    }
}
