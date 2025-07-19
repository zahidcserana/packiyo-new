<?php

namespace App\Models;

use Database\Factories\WarehouseFactory;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Igaster\LaravelCities\Geo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\Warehouse
 *
 * @property int $id
 * @property int $customer_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read ContactInformation|null $contactInformation
 * @property-read Customer $customer
 * @property-read mixed $information
 * @property-read Collection|Location[] $locations
 * @property-read int|null $locations_count
 * @property-read Collection|Order[] $orders
 * @property-read int|null $orders_count
 * @method static WarehouseFactory factory(...$parameters)
 * @method static Builder|Warehouse newModelQuery()
 * @method static Builder|Warehouse newQuery()
 * @method static \Illuminate\Database\Query\Builder|Warehouse onlyTrashed()
 * @method static Builder|Warehouse query()
 * @method static Builder|Warehouse whereCreatedAt($value)
 * @method static Builder|Warehouse whereCustomerId($value)
 * @method static Builder|Warehouse whereDeletedAt($value)
 * @method static Builder|Warehouse whereId($value)
 * @method static Builder|Warehouse whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Warehouse withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Warehouse withoutTrashed()
 * @mixin \Eloquent
 * @property-read Collection|ProductWarehouse[] $productWarehouses
 * @property-read int|null $product_warehouses_count
 * @property-read Collection|Shipment[] $shipments
 * @property-read int|null $shipments_count
 */
class Warehouse extends Model
{
    use SoftDeletes, CascadeSoftDeletes, HasFactory;

    protected $cascadeDeletes = [
        'locations',
        'contactInformation'
    ];

    protected $fillable = [
        'customer_id'
    ];

    public function contactInformation()
    {
        return $this->morphOne(ContactInformation::class, 'object')->withTrashed();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function getInformationAttribute()
    {
        return implode(', ', [
            $this->contactInformation->name,
            $this->contactInformation->email,
            $this->contactInformation->zip,
            $this->contactInformation->city,
        ]);
    }

    public function reshipLocation()
    {
        return Location::where('name', Location::PROTECTED_LOC_NAME_RESHIP)->where('warehouse_id', $this->id)->first();
    }

    /**
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany
     */
    public function productWarehouses(): HasMany
    {
        return $this->hasMany(ProductWarehouse::class);
    }

    /**
     * @return HasMany
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function timezone(): string
    {
        return static::getTimezoneByWarehouse()
            ?? static::getTimezoneByFirstUser($this->customer)
            ?? env('DEFAULT_TIME_ZONE');
    }

    protected function getTimezoneByWarehouse(): ?string
    {
        $coordinates = Geo::where([
            'name' => $this->contactInformation->city,
            'country' => $this->contactInformation->iso_3166_2
        ])->first();

        // TODO: Find a way to do this without a library that hits an external API.
        return null;
    }

    protected function getTimezoneByFirstUser(Customer $customer): ?string
    {
        $firstUser = $customer->users()->orderBy('created_at')->first();

        if (is_null($firstUser)) {
            return null;
        }

        return user_settings(UserSetting::USER_SETTING_TIMEZONE, user_id: $firstUser->id, default: env('DEFAULT_TIME_ZONE'));
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'customer_user', 'warehouse_id', 'user_id');
    }

    // required for SetWarehouseActionSchema. Not sure about this
    public function getNameAttribute(): string
    {
        return $this->contactInformation->name ?? '';
    }
}
