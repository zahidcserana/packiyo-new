<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\Attachment
 *
 * @property int $id
 * @property string $object_type
 * @property string $name
 * @property int $object_id
 * @property string|null $url
 * @property string|null $printer_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Link extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'object_id',
        'object_type',
        'name',
        'url',
        'is_printable',
        'printer_type'
    ];

    protected $casts = [
        'is_printable' => 'bool',
    ];

    protected $table = 'links';

    public function object(): MorphTo
    {
        return $this->morphTo();
    }
}
