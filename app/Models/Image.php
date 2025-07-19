<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Illuminate\Support\Carbon;
use App\Traits\Audits\ImageAudit;

/**
 * App\Models\Image
 *
 * @property int $id
 * @property string $object_type
 * @property int $object_id
 * @property string|null $source
 * @property string|null $filename
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Model|\Eloquent $object
 * @method static Builder|Image newModelQuery()
 * @method static Builder|Image newQuery()
 * @method static \Illuminate\Database\Query\Builder|Image onlyTrashed()
 * @method static Builder|Image query()
 * @method static Builder|Image whereCreatedAt($value)
 * @method static Builder|Image whereDeletedAt($value)
 * @method static Builder|Image whereFilename($value)
 * @method static Builder|Image whereId($value)
 * @method static Builder|Image whereObjectId($value)
 * @method static Builder|Image whereObjectType($value)
 * @method static Builder|Image whereSource($value)
 * @method static Builder|Image whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Image withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Image withoutTrashed()
 * @mixin \Eloquent
 */
class Image extends Model implements AuditableInterface
{
    use SoftDeletes;

    use AuditableTrait, ImageAudit {
        ImageAudit::transformAudit insteadof AuditableTrait;
    }

    protected $fillable = [
        'source',
        'filename',
        'object_id',
        'object_type'
    ];

    /**
     * Audit configs
     */
    protected $auditStrict = true;

    protected $auditInclude = [
        'source'
    ];

    public function object()
    {
        return $this->morphTo();
    }
}
