<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\ProductBarcode
 *
 * @property int $id
 * @property int $product_id
 * @property string $barcode
 * @property int $quantity
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \App\Models\product $product
 * @method static \Illuminate\Database\Eloquent\Builder|ProductBarcode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductBarcode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductBarcode query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductBarcode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductBarcode whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductBarcode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductBarcode whereBarcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductBarcode whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductBarcode whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductBarcode whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProductBarcode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'barcode',
        'quantity',
        'description',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
