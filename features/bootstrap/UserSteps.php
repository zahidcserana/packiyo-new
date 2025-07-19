<?php

use App\Models\{
    User,
    UserRole,
    ContactInformation,
    UserSetting,
    Currency,
    Customer
};
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\NewAccessToken;

/**
 * Behat steps to test users.
 */
trait UserSteps
{
    /**
     * @Given a member user :email named :firstName based in :country
     */
    public function aMemberUserNamedBasedIn(string $email, string $firstName, string $countryName): void
    {
        $country = \Countries::where('name', $countryName)->firstOrFail();
        $user = User::factory()->create([
            'email' => $email,
            'password' => \Hash::make('secret'),
            'user_role_id' => UserRole::ROLE_MEMBER
        ]);
        ContactInformation::factory()->create([
            'object_type' => User::class,
            'object_id' => $user->id,
            'country_id' => $country->id,
            'name' => $firstName,
            'email' => $email
        ]);

        // Default USD currency
        Currency::factory()->create([
            'name' => 'United States Dollar',
            'code' => 'USD',
            'symbol' => '$',
            'exchange_rate' => 1,
            'active' => 1,
        ]);

        $this->defineUserScope($user);
    }

    /**
     * @Given an admin user :email named :firstName based in :country
     */
    public function anAdminUserNamedBasedIn(string $email, string $firstName, string $countryName): void
    {
        $country = \Countries::where('name', $countryName)->firstOrFail();
        $user = User::factory()->create([
            'email' => $email,
            'password' => \Hash::make('secret'),
            'user_role_id' => UserRole::ROLE_ADMINISTRATOR
        ]);
        ContactInformation::factory()->create([
            'object_type' => User::class,
            'object_id' => $user->id,
            'country_id' => $country->id,
            'name' => $firstName,
            'email' => $email
        ]);

        $this->defineUserScope($user);
    }

    protected function defineUserScope(User $customer): void
    {
        if (
            method_exists($this, 'hasUserInScope')
            && !$this->hasUserInScope()
            && method_exists($this, 'setUserInScope')
        ) {
            $this->setUserInScope($customer);
        }
    }

    /**
     * @Given the user :email has the setting :key set to :value
     */
    public function theUserHasTheSettingSetTo(string $email, string $key, string $value): void
    {
        $user = User::where('email', $email)->firstOrFail();
        UserSetting::factory()->create([
            'user_id' => $user->id,
            'key' => $key,
            'value' => $value
        ]);
    }

    /**
     * @Given the user :email is authenticated
     */
    public function theUserIsAuthenticated(string $email): void
    {
        Auth::setUser(User::where('email', $email)->firstOrFail());
    }

    /**
     * @Then the authenticated user is :email
     */
    public function theAuthenticatedUserIs(string $email): void
    {
        $this->assertTrue(Auth::hasUser());
        $this->assertEquals(User::where('email', $email)->firstOrFail()->id, Auth::user()->id);
    }

    /**
     * @Given the user has an API access token named :name with the ability :ability
     */
    public function theUserHasAnApiAccessTokenNamedWithTheAbility(string $name, string $ability): NewAccessToken
    {
        $token = $this->getUserInScope()->createToken($name, [$ability]);

        $this->defineTokenScope($token);

        return $token;
    }

    /**
     * @Given the user has an API access token named :name with the ability :ability and assigned to customer :customerName
     */
    public function theUserHasAnApiAccessTokenNamedWithTheAbilityAndAssignedToCustomer(string $name,
                                                                                       string $ability,
                                                                                       string $customerName): void
    {
        $token = $this->theUserHasAnApiAccessTokenNamedWithTheAbility($name, $ability);

        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $token->accessToken->customer_id = $customer->id;
        $token->accessToken->save();
    }

    /**
     * @Then I will work with user :email
     */
    public function iWillWorkWithUser(string $email): void
    {
        $user = User::where('email', $email)->firstOrFail();
        $this->setUserInScope($user);
    }

    protected function defineTokenScope(NewAccessToken $token): void
    {
        if (
            method_exists($this, 'hasTokenInScope')
            && !$this->hasTokenInScope()
            && method_exists($this, 'setTokenInScope')
        ) {
            $this->setTokenInScope($token);
        }
    }
}
