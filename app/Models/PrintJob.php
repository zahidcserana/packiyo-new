<?php

namespace App\Models;

use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\PrintJob
 *
 * @property int $id
 * @property string $object_type
 * @property int $object_id
 * @property string|null $url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string|null $job_start
 * @property string|null $job_end
 * @property int $printer_id
 * @property string|null $job_id_system
 * @property string|null $status
 * @property int $user_id
 * @property-read Model|\Eloquent $object
 * @property-read Printer $printer
 * @property-read User $user
 * @method static Builder|PrintJob newModelQuery()
 * @method static Builder|PrintJob newQuery()
 * @method static \Illuminate\Database\Query\Builder|PrintJob onlyTrashed()
 * @method static Builder|PrintJob query()
 * @method static Builder|PrintJob whereCreatedAt($value)
 * @method static Builder|PrintJob whereDeletedAt($value)
 * @method static Builder|PrintJob whereId($value)
 * @method static Builder|PrintJob whereJobEnd($value)
 * @method static Builder|PrintJob whereJobIdSystem($value)
 * @method static Builder|PrintJob whereJobStart($value)
 * @method static Builder|PrintJob whereObjectId($value)
 * @method static Builder|PrintJob whereObjectType($value)
 * @method static Builder|PrintJob wherePrinterId($value)
 * @method static Builder|PrintJob whereStatus($value)
 * @method static Builder|PrintJob whereUpdatedAt($value)
 * @method static Builder|PrintJob whereUrl($value)
 * @method static Builder|PrintJob whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|PrintJob withTrashed()
 * @method static \Illuminate\Database\Query\Builder|PrintJob withoutTrashed()
 * @mixin \Eloquent
 */
class PrintJob extends Model
{
    use SoftDeletes, CascadeSoftDeletes, HasFactory;

    protected $table = 'print_jobs';

    public const STATUS_PRINTED = 'PRINTED';

    protected $fillable = [
        'object_type',
        'object_id',
        'url',
        'type',
        'job_start',
        'job_end',
        'printer_id',
        'job_id_system',
        'status',
        'user_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function printer(): BelongsTo
    {
        return $this->belongsTo(Printer::class);
    }

    public function object(): MorphTo
    {
        return $this->morphTo();
    }
}
