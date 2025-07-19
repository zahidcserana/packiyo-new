<?php

namespace App\Models;

use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderChannel extends Model
{
    use HasFactory, SoftDeletes, CascadeSoftDeletes;

    protected $fillable = [
        'customer_id',
        'name',
        'settings',
        'image_url'
    ];

    protected $casts = [
        'settings' => 'array'
    ];

    protected $attributes = [
        'settings' => '{}'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function webhooks()
    {
        return $this->hasMany(Webhook::class);
    }

    public function credential()
    {
        return $this->morphTo();
    }
}
