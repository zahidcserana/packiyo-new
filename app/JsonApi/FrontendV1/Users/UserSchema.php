<?php

namespace App\JsonApi\FrontendV1\Users;

use App\Models\Country;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use LaravelJsonApi\Core\Json\Hash;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Map;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOne;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class UserSchema extends Schema
{

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = User::class;

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('email'),
            Str::make('picture')->extractUsing(static function (User $user) {
                return $user->picture ? url(Storage::url($user->picture)) : '';
            })->readOnly(),
            Str::make('timezones')->extractUsing(static function (User $user) {
                return config('settings.timezones');
            })->readOnly(),
            Str::make('locales')->extractUsing(static function (User $user) {
                return [
                    'en' => __('English'),
                    'no' => __('Norwegian'),
                    'da' => __('Danish')
                ];
            })->readOnly(),
            Str::make('accounts')->extractUsing(static function (User $user) {
                return $user->getAccounts();
            })->readOnly(),
            Str::make('customer_user_roles')->extractUsing(static function (User $user) {
                return $user->getCustomerUserRoles();
            })->readOnly(),
            Str::make('countries')->extractUsing(static function (User $user) {
                return Country::all()->pluck('name', 'id');
            })->readOnly(),
            Str::make('currencies')->extractUsing(static function (User $user) {
                return Currency::all()->pluck('code', 'id');
            })->readOnly(),
            HasOne::make('contact_information','contactInformation'),
            Boolean::make('is_admin')
                ->extractUsing(static function (User $user) {
                    return $user->isAdmin();
                })->readOnly(),
            Boolean::make('is_super_admin')
                ->extractUsing(static function (User $user) {
                    return $user->isSuperAdmin();
                })->readOnly(),
            HasMany::make('user_settings'),
        ];
    }

    /**
     * Get the resource filters.
     *
     * @return array
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
        ];
    }

    /**
     * Get the resource paginator.
     *
     * @return Paginator|null
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make()->withSimplePagination();
    }

}
