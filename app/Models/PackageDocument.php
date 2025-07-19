<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\PackageDocument
 *
 * @property int $id
 * @property int|null $package_id
 * @property string|null $size
 * @property string|null $url
 * @property mixed|null $content
 * @property string|null $document_type
 * @property string|null $type
 * @property int $submitted_electronically
 * @property int $print_with_label
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Package|null $package
 * @method static \Illuminate\Database\Eloquent\Builder|PackageDocument newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageDocument newQuery()
 * @method static \Illuminate\Database\Query\Builder|PackageDocument onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageDocument query()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageDocument whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageDocument whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageDocument whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageDocument whereDocumentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageDocument whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageDocument wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageDocument wherePrintWithLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageDocument whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageDocument whereSubmittedElectronically($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageDocument whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageDocument whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageDocument whereUrl($value)
 * @method static \Illuminate\Database\Query\Builder|PackageDocument withTrashed()
 * @method static \Illuminate\Database\Query\Builder|PackageDocument withoutTrashed()
 * @mixin \Eloquent
 */
class PackageDocument extends Model
{
    use SoftDeletes;

    public const TYPE_COMMERCIAL_INVOICE = 'commercial_invoice';

    protected $guarded = [];

    protected $hidden = [
        'content'
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
