<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookJobsFailure extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_uuid',
        'attempt_count',
        'failed_type',
        'count'
    ];
}
