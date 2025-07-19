<?php

namespace App\Models;

use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\AddressBook
 *
 * @property int $id
 * @property string $name
 * @property int $customer_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read ContactInformation $contactInformation
 * @property-read \App\Models\Customer $customer
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AddressBook newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AddressBook newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AddressBook onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AddressBook query()
 * @method static bool|null restore()
 * @method static Builder|AddressBook whereContactInformationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AddressBook whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AddressBook whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AddressBook whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AddressBook whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AddressBook whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AddressBook whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AddressBook withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\AddressBook withoutTrashed()
 * @mixin \Eloquent
 */
class AddressBook extends Model
{
    use HasFactory, SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = [
        'contactInformation'
    ];

    protected $fillable = [
        'customer_id',
        'name',
    ];

    public function contactInformation()
    {
        return $this->morphOne(ContactInformation::class, 'object')->withTrashed();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->with('contactInformation')->withTrashed();
    }

    public function getInformationAttribute()
    {
        $address = implode(', ', [
            $this->name,
            $this->contactInformation->name,
            $this->contactInformation->address,
            $this->contactInformation->city,
        ]);

        $prepend = app('user')->getSessionCustomer() && app('user')->getSessionCustomer()->is3pl() && $this->customer->parent_id ? '-- ' : '';

        return $prepend . $address;
    }
}
