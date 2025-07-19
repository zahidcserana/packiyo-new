<?php

namespace App\Components;

use App\Http\Requests\AccessTokenRequest;
use App\Http\Requests\User\DestroyBatchRequest;
use App\Http\Requests\User\DestroyRequest;
use App\Http\Requests\User\StoreBatchRequest;
use App\Http\Requests\User\StoreRequest;
use App\Http\Requests\User\UpdateBatchRequest;
use App\Http\Requests\User\UpdateRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\Customer;
use App\Models\CustomerUser;
use App\Models\User;
use App\Models\UserSetting;
use App\Models\Webhook;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Laravel\Sanctum\PersonalAccessToken;

class UserComponent extends BaseComponent
{
    public const SESSION_CUSTOMER_KEY = 'session_customer';

    private ?Customer $sessionCustomer = null;

    public function store(StoreRequest $request, $fireWebhook = true)
    {
        $input = $request->validated();

        if ($user = User::withTrashed()->where("email", $input['email'])->first()) {
            $user->restore();

            $user->contactInformation->update($input['contact_information']);

            if (!empty($input['password'])) {
                $input['password'] = Hash::make($input['password']);
            } else {
                unset($input['password']);
            }

            $user->update($input);

            if ($fireWebhook) {
                $this->userWebhook(new UserResource($user), Webhook::OPERATION_TYPE_UPDATE, $user->customers);
            }
        } else {
            $input['password'] = \Hash::make($input['password']);

            $contactInformationData = Arr::get($input, 'contact_information');
            Arr::forget($input, 'contact_information');

            $user = User::create($input);

            $customerId = $request->get('customer_id');
            $customerUserRoleId = $request->get('customer_user_role_id');

            if ($customerId && $customerUserRoleId) {
                $warehouseId = $request->get('warehouse_id');

                if ($warehouseId) {
                    $user->warehouses()->attach($warehouseId, ['customer_id' => $customerId, 'role_id' => $customerUserRoleId]);
                } else {
                    $user->customers()->attach($customerId, ['role_id' => $customerUserRoleId]);
                }
            }

            $this->createContactInformation($contactInformationData, $user);

            if ($fireWebhook) {
                $this->userWebhook(new UserResource($user), Webhook::OPERATION_TYPE_STORE, $user->customers);
            }
        }

        return $user;
    }

    public function storeBatch(StoreBatchRequest $request): Collection
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $storeRequest = StoreRequest::make($record);
            $responseCollection->add($this->store($storeRequest, false));
        }

        $this->userBatchWebhook($responseCollection, UserCollection::class, Webhook::OPERATION_TYPE_STORE);

        return $responseCollection;
    }

    public function disable(User $user)
    {
        return $user->update(['disabled_at' => now()]);
    }

    public function enable(User $user)
    {
        return $user->update(['disabled_at' => null]);
    }

    public function update(UpdateRequest $request, User $user, $fireWebhook = true): User
    {
        $input = $request->validated();

        $user->contactInformation->update($input['contact_information']);

        if (!empty($input['password'])) {
            $input['password'] = Hash::make($input['password']);
        } else {
            unset($input['password']);
        }

        if (isset($input['disabled_at'])) {
            $input['disabled_at'] = null;
        } else {
            $input['disabled_at'] = now();
        }

        $user->update($input);

        $warehouseId = $input['warehouse_id'];

        $userCustomer = CustomerUser::whereUserId($user->id)
            ->whereCustomerId($this->getSessionCustomer()->id)
            ->first();

        if ($userCustomer) {
            if ($warehouseId) {
                $userCustomer->warehouse_id = $warehouseId;
            } else {
                $userCustomer->warehouse_id = null;
            }
        }

        $userCustomer->save();

        if ($fireWebhook) {
            $this->userWebhook(new UserResource($user), Webhook::OPERATION_TYPE_UPDATE, $user->customers);
        }

        return $user;
    }

    public function updateBatch(UpdateBatchRequest $request): Collection
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $updateRequest = UpdateRequest::make($record);
            $user = User::where('email', $record['email'])->first();

            $responseCollection->add($this->update($updateRequest, $user, false));
        }

        $this->userBatchWebhook($responseCollection, UserCollection::class, Webhook::OPERATION_TYPE_UPDATE);

        return $responseCollection;
    }

    public function destroy(DestroyRequest $request = null, User $user = null, Customer $customer = null, $fireWebhook = true)
    {
        $customers = $user->customers;
        if (!$customer) {

            $user->delete();
        } else {
            $user->customers()->detach($customer->id);

            if (!$user->customers()->count()) {
                $user->delete();
            }
        }

        $response = ['email' => $user->email, 'customers' => $customers ?? []];

        if ($fireWebhook) {
            $this->userWebhook($response, Webhook::OPERATION_TYPE_DESTROY, $customers);
        }

        return $response;
    }

    public function destroyBatch(DestroyBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $destroyRequest = DestroyRequest::make($record);
            $user = User::where('email', $record['email'])->first();
            $customer = null;

            if (!empty($record['customer_id'])) {
                $customer = Customer::find($record['customer_id']);
            }

            $responseCollection->add($this->destroy($destroyRequest, $user, $customer, false));
        }

        $this->userBatchWebhook($responseCollection, ResourceCollection::class, Webhook::OPERATION_TYPE_DESTROY);

        return $responseCollection;
    }

    public function getCustomers(): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isAdmin()) {
            return Customer::all();
        }

        $customers = $user->customers->pluck('id')->toArray();

        return Customer::withClients($customers)
            ->get();
    }

    function sortCustomers($customerCollection, $parentId = null)
    {
        $sortedCustomers = collect();

        $customers = $customerCollection->where('parent_id', $parentId)->sortBy('contactInformation.name');

        if ($customers->isEmpty() && $parentId === null) {
            $customers = $customerCollection->sortBy('contactInformation.name');

            foreach ($customers as $customer) {
                $sortedCustomers->push($customer);
            }
        } else {
            foreach ($customers as $customer) {
                $sortedCustomers->push($customer);
                $sortedCustomers = $sortedCustomers->merge($this->sortCustomers($customerCollection, $customer['id']));
            }
        }

        return $sortedCustomers;
    }

    public function getSortedCustomers(): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isAdmin() && !$user->customers->count()) {
            $customers = Customer::all();
        } else {
            $customers = $user->customers->pluck('id')->toArray();
            $customers = Customer::withClients($customers)->get();
        }

        return $this->sortCustomers($customers);
    }

    public function createAccessToken(AccessTokenRequest $request, $abilities = ['*'], ?Customer $customer = null)
    {
        $input = $request->validated();

        $token = auth()->user()->createToken($input['name'], $abilities);

        if ($customer) {
            $token->accessToken->customer_id = $customer->id;
            $token->accessToken->save();
        }

        return $token;
    }

    /**
     * @param PersonalAccessToken $token
     * @return bool|null
     */
    public function deleteAccessToken(PersonalAccessToken $token): ?bool
    {
        return $token->delete();
    }

    public function getCustomerUsers(Customer $customer): \Illuminate\Database\Eloquent\Collection
    {
        return $customer->users()->get();
    }

    public function userBatchWebhook($collections, $resourceCollection, $operation): void
    {
        $customerWiseItems = [];

        foreach ($collections as $item) {
            $customers = $operation == Webhook::OPERATION_TYPE_DESTROY ? $item['customers'] : $item->customers;

            if (count($customers) === 0) {
                continue;
            }

            foreach ($customers as $customer) {
                $customerId = $customer->id;

                unset($item['customers']);

                $customerWiseItems[$customerId][] = $item;
            }
        }

        foreach ($customerWiseItems as $customerId => $users) {
            $collections = new Collection($users);

            $this->webhook(new $resourceCollection($collections), User::class, $operation, $customerId);
        }
    }

    public function setDefaultTimezoneInUserSetting($request, $user): bool
    {
        $userSetting = user_settings(UserSetting::USER_SETTING_TIMEZONE);

        if (!$userSetting) {
            app('user')->storeSettings($user, [UserSetting::USER_SETTING_TIMEZONE => $request->timezone]);
        }

        // Store or update users settings
        UserSetting::saveSettings($request->only(['timezone']));

        return true;
    }

    protected function userWebhook($response, $operation, $customers): bool
    {
        if (is_null($customers)) {
            return false;
        }

        foreach ($customers as $customer) {
            unset($response['customers']);

            $this->webhook($response, User::class, $operation, $customer->id);
        }

        return true;
    }

    /**
     * @return Customer|Customer[]|\Illuminate\Database\Eloquent\Collection|Model|mixed|null
     */
    public function getSessionCustomer()
    {
        $customer = Session::get(self::SESSION_CUSTOMER_KEY);

        if (!$customer) {
            return null;
        }

        if (is_int($customer)) {
            if (!$this->sessionCustomer || $this->sessionCustomer->id != $customer) {
                $this->sessionCustomer = Customer::find($customer);
            }
        }

        return $this->sessionCustomer;
    }

    public function setSessionCustomer(Customer $customer): void
    {
        Session::put(self::SESSION_CUSTOMER_KEY, $customer->id);
    }

    public function removeSessionCustomer(): void
    {
        Session::forget(self::SESSION_CUSTOMER_KEY);
    }

    /**
     * @return Collection
     */
    public function getSelectedCustomers(): Collection
    {
        $customer = $this->getSessionCustomer();

        if (!is_null($customer)) {
            $customers = new Collection();
            $customers->add($customer);

            foreach ($customer->children as $childCustomer) {
                $customers->add($childCustomer);
            }

            return $customers;
        }

        return $this->getCustomers();
    }

    public function get3plCustomers()
    {
        $user = auth()->user();
        $customers = $user->customers()->get();

        $customerCollection = new Collection();

        foreach ($customers as $customer) {
            foreach ($customer->children as $childCustomer) {
                $customerCollection->add($childCustomer);
            }
        }

       return Customer::with('contactInformation')->whereIn('id', $customerCollection->pluck('id')->toArray())->get();
    }

    public function filterCustomers(Request $request, bool $only3pl = false): JsonResponse
    {
        $customerIds = $this->getSelectedCustomers()->pluck('id')->toArray();
        $term = $request->get('term');

        $results = [];

        if ($term) {
            $customerQuery = Customer::whereIn('id', $customerIds);

            if ($only3pl) {
                $customerQuery->where(['parent_id' => null, 'allow_child_customers' => true]);
            }

            $customerQuery->whereHas('contactInformation', static function($query) use ($term) {
                $term = $term . '%';

                $query->where('name', 'like', $term)
                    ->orWhere('company_name', 'like', $term)
                    ->orWhere('email', 'like',  $term)
                    ->orWhere('zip', 'like', $term)
                    ->orWhere('city', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });

            foreach ($customerQuery->get() as $customer) {
                if ($customer->contactInformation) {
                    $results[] = [
                        'id' => $customer->id,
                        'text' => $customer->contactInformation->name
                    ];
                }
            }
        }

        return response()->json([
            'results' => $results
        ]);
    }

    public function storeSettings(User $user, $settings)
    {
        foreach ($settings as $setting => $value) {
            if (in_array($setting, UserSetting::USER_SETTING_KEYS)) {
                UserSetting::updateOrCreate(
                    ['user_id' => $user->id, 'key' => $setting],
                    ['value' => $value]
                );
            }

            Cache::put('user_setting_' . $user->id . '_' . $setting, $value);
        }
    }

    /**
     * Gets the 3PL customer of the non-admin logged user, if any.
     *
     * We are currently excluding admins entirely because they are not bound to a customer at all.
     */
    public function get3plCustomer(): Customer|null
    {
        /** @var Customer|null $customer */
        $sessionCustomer = $this->getSessionCustomer();

        if ($sessionCustomer) {
            return $sessionCustomer->is3pl() ? $sessionCustomer : null;
        }

        /** @var User $user */
        $user = auth()->user();
        $selectedCustomers = $this->getSelectedCustomers();

        if (
            is_null($user)
            || $selectedCustomers->filter(fn (Customer $customer) => $customer->is3pl())->count() > 1
        ) {
            return null;
        }

        return $selectedCustomers->first(fn (Customer $customer) => $customer->is3pl());
    }

    /**
     * @param Customer $customer
     * @return null
     */
    public function getCustomerWarehouseId(Customer $customer)
    {
        $customerId = $customer->id;

        if ($customer->parent_id) {
            $customerId = $customer->parent_id;
        }

        $userCustomer = auth()->user()->customers
            ->where('pivot.customer_id', $customerId)
            ->first();

        if ($userCustomer) {
            return $userCustomer->pivot->warehouse_id;
        }

        return null;
    }

    /*
     * @return bool
     */
    public function isClientCustomer(): bool
    {
        $sessionCustomer = $this->getSessionCustomer();

        if ($sessionCustomer && $sessionCustomer->parent_id) {
            return true;
        }

        return false;
    }
}
