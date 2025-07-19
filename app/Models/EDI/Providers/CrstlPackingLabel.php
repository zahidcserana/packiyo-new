<?php

namespace App\Models\EDI\Providers;

use Illuminate\Database\Eloquent\Model;

class CrstlPackingLabel extends Model
{
    protected $fillable = [
        'label_type',
        'signed_url',
        'signed_url_expires_at',
        'content'
    ];

    protected $dates = [
        'signed_url_expires_at'
    ];

    protected $hidden = [
        'content'
    ];

    public function asn()
    {
        return $this->belongsTo(CrstlASN::class, foreignKey: 'asn_id')->withTrashed();
    }
}
