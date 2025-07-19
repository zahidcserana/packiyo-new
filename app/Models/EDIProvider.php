<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Parental\HasChildren;

class EDIProvider extends Model
{
    use HasFactory, SoftDeletes, HasChildren;

    protected $table = 'edi_providers';

    protected $fillable = [
        'name',
        'active'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }
}
