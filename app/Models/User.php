<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Venturecraft\Revisionable\RevisionableTrait;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $email
 * @property string $password
 * @property string|null $picture
 * @property int $user_role_id
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read ContactInformation $contactInformation
 * @property-read Collection|Customer[] $customers
 * @property-read int|null $customers_count
 * @property-read Collection|InventoryLog[] $inventoryLogs
 * @property-read int|null $inventory_logs_count
 * @property-read DatabaseNotificationCollection|DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read UserRole $role
 * @property-read Collection|Task[] $tasks
 * @property-read int|null $tasks_count
 * @method static bool|null forceDelete()
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static \Illuminate\Database\Query\Builder|User onlyTrashed()
 * @method static Builder|User query()
 * @method static bool|null restore()
 * @method static Builder|User whereContactInformationId($value)
 * @method static Builder|User whereCreatedAt($value)
 * @method static Builder|User whereDeletedAt($value)
 * @method static Builder|User whereEmail($value)
 * @method static Builder|User whereId($value)
 * @method static Builder|User wherePassword($value)
 * @method static Builder|User wherePicture($value)
 * @method static Builder|User whereRememberToken($value)
 * @method static Builder|User whereRoleId($value)
 * @method static Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|User withTrashed()
 * @method static \Illuminate\Database\Query\Builder|User withoutTrashed()
 * @mixin \Eloquent
 * @property-read Collection|Webhook[] $webhooks
 * @property-read int|null $webhooks_count
 * @method static Builder|User whereUserRoleId($value)
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, RevisionableTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email',
        'password',
        'picture',
        'user_role_id',
        'disabled_at',
        'last_login_at',
        'system_user'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $attributes = [
        'user_role_id' => UserRole::ROLE_DEFAULT
    ];

    protected $dontKeepRevisionOf = [
        'password'
    ];

    public static array $authAdditionalFields = [
        'disabled_at' => null
    ];

    public function customerIds($includeDisabled = false, $ignoreSession = false, $useSelf = false)
    {
        $user = $useSelf ? $this : Auth()->user();
        $sessionCustomer = app()->user->getSessionCustomer();

        if ($sessionCustomer && !$ignoreSession) {
            return [$sessionCustomer->id];
        }

        if ($user->isAdmin()) {
            return Customer::all()->pluck('id')->toArray();
        }

        if ($includeDisabled) {
            return $user->customers->pluck('id')->toArray();
        }

        return $user->customers->where('active', 1)->pluck('id')->toArray();
    }

    public function contactInformation()
    {
        return $this->morphOne(ContactInformation::class, 'object')->withTrashed();
    }

    public function userHash()
    {
        return hash_hmac(
            'sha256',
            $this->email,
            config('intercom.hash_key')
        );
    }

    /**
     * Get the role of the user
     *
     * @return BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(UserRole::class, 'user_role_id', 'id');
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class)
            ->using(CustomerUser::class)
            ->withPivot([
                'role_id',
                'warehouse_id'
            ]);
    }

    public function getAccounts()
    {
        return app('user')->getSortedCustomers();
    }

    public function getCustomerUserRoles()
    {
        return CustomerUser::where('user_id', $this->id)->get();
    }

    public function clients()
    {
        $customerIds = $this->customers->pluck('id')->toArray();

        return Customer::withClients($customerIds)
            ->with('contactInformation')
            ->get()
            ->map(fn ($customer) => optional($customer->contactInformation)->name)
            ->filter()
            ->implode(', ');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function placedToteItems()
    {
        return $this->hasMany(ToteOrderItem::class)->whereRaw('quantity_remaining > 0')->withTrashed();
    }

    public function inventoryLogs()
    {
        return $this->hasMany(InventoryLog::class);
    }

    public function settings()
    {
        return $this->hasMany(UserSetting::class);
    }

    public function userSettings()
    {
        return $this->hasMany(UserSetting::class);
    }

    /**
     * Get the path to the profile picture
     *
     * @return string
     */
    public function profilePicture()
    {
        return $this->picture ? "/storage/$this->picture" : null;
    }

    public function disabled()
    {
        return $this->disabled_at;
    }

    /**
     * Check if the user has admin role
     *
     * @return boolean
     */
    public function isAdmin()
    {
        return $this->user_role_id == UserRole::ROLE_ADMINISTRATOR;
    }

    public function isSuperAdmin()
    {
        return $this->id == 1;
    }

    public function webhooks()
    {
        return $this->hasMany(Webhook::class);
    }

    public function accessibleCustomerIds($includeClients = true)
    {
        if ($includeClients) {
            $customerIds = $this->customers()->with('children')
                ->get()
                ->flatMap(fn (Customer $customer) => array_merge([$customer->id], $customer->children->pluck('id')->toArray()))
                ->toArray();
        } else {
            $customerIds = $this->customers()->pluck('customer_id')->toArray();
        }

        return $customerIds;
    }

    public function canAccessCustomer($customerId, $includeClients = true)
    {
        return in_array($customerId, $this->accessibleCustomerIds($includeClients));
    }

    public function printers()
    {
        return $this->hasMany(Printer::class);
    }

    public function orderLocks()
    {
        return $this->hasMany(OrderLock::class);
    }

    public function labelPrinter()
    {
        // TODO: These is calling the user_settings() helper incorrectly.
        return Printer::find(user_settings($this->id, UserSetting::USER_SETTING_LABEL_PRINTER_ID));
    }

    public function barcodePrinter()
    {
        // TODO: These is calling the user_settings() helper incorrectly.
        return Printer::find(user_settings($this->id, UserSetting::USER_SETTING_BARCODE_PRINTER_ID));
    }

    public function slipPrinter()
    {
        // TODO: These is calling the user_settings() helper incorrectly.
        return Printer::find(user_settings($this->id, UserSetting::USER_SETTING_SLIP_PRINTER_ID));
    }

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'customer_user', 'user_id', 'warehouse_id')
            ->withPivot([
                'customer_id',
                'role_id'
            ]);
    }
}
