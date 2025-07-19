<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Audit as AuditInterface;
use OwenIt\Auditing\Audit as AuditTrait;

class Audit extends Model implements AuditInterface
{
    use AuditTrait;

    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * {@inheritdoc}
     */
    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
        'custom_message' => 'string',
        'auditable_id' => 'integer', // Non-integer PK models cannot be audited, apparently.
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * {@inheritdoc}
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * {@inheritdoc}
     */
    public function parentable()
    {
        return $this->morphTo();
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        return $this->morphTo();
    }

    public function getDisplayDateAttribute()
    {
        return user_date_time($this->created_at->toDateString(), true);
    }

    public function ranByAutomation(): BelongsTo
    {
        return $this->belongsTo(Automation::class, 'ran_by_automation_id');
    }
}
