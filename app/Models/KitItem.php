<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * App\Models\KitItem
 *
 * @property int $id
 * @property int $parent_product_id
 * @property int $child_product_id
 * @property int $quantity
 * @property-read \App\Models\Product|null $component
 * @property-read \App\Models\Product|null $kit
 * @method static \Illuminate\Database\Eloquent\Builder|KitItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|KitItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|KitItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|KitItem whereComponentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KitItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KitItem whereKitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|KitItem whereQuantity($value)
 * @mixin \Eloquent
 */
class KitItem extends Pivot
{
    use HasFactory;

    protected $table = 'kit_items';

    public $timestamps = null;

    protected $fillable = [
        'parent_product_id',
        'child_product_id',
        'quantity',
    ];

    public function kit()
    {
        return $this->hasOne(Product::class, 'id', 'parent_product_id')->withTrashed();
    }

    public function component()
    {
        return $this->hasOne(Product::class, 'id', 'child_product_id')->withTrashed();
    }
}
