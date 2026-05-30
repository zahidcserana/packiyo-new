<?php

namespace App\Components;

use App\Http\Requests\Customer\DestroyBatchRequest;
use App\Http\Requests\Customer\DestroyRequest;
use App\Http\Requests\Customer\StoreBatchRequest;
use App\Http\Requests\Customer\StoreRequest;
use App\Http\Requests\Customer\UpdateBatchRequest;
use App\Http\Requests\Customer\UpdateRateCardsRequest;
use App\Http\Requests\Customer\UpdateRequest;
use App\Http\Requests\Customer\UpdateUsersRequest;
use App\Models\Customer;
use App\Models\CustomerSetting;
use App\Models\CustomerUser;
use App\Models\Image;
use App\Models\RateCard;
use App\Models\ShippingCarrier;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CustomerComponent extends BaseComponent
{
    public function store(FormRequest $request)
    {
        $user = auth()->user();
        $input = $request->validated();

        $contactInformationData = Arr::get($input, 'contact_information');

        $input['allow_child_customers'] = Arr::get($input, 'allow_child_customers') === '1';

        if ($input['allow_child_customers']) {
            Arr::forget($input, 'parent_customer_id');
        }

        if (Arr::get($input, 'ship_from_contact_information_id') == 'none') {
            $input['ship_from_contact_information_id'] = null;
        }

        if (Arr::get($input, 'return_to_contact_information_id') == 'none') {
            $input['return_to_contact_information_id'] = null;
        }

        $customer = Customer::create($input);

        if ($user && !$user->isAdmin()) {
            $user->customers()->attach($customer, ['role_id' => UserRole::ROLE_DEFAULT]);
        }

        if (Arr::exists($input, 'parent_customer_id') && auth()->user()->isAdmin()) {
            $customer->parent_id = Arr::get($input, 'parent_customer_id');
            Arr::forget($input, 'parent_customer_id');
        } elseif (!is_null(app()->user->getSessionCustomer())) {
            $customer->parent_id = Arr::get($input, 'parent_customer_id');
            Arr::forget($input, 'parent_customer_id');
        }

        $customer->save();

        $this->createContactInformation($contactInformationData, $customer);

        if ($customer->isNotChild()) {
            $this->createPrimaryWarehouse($contactInformationData, $customer);
        }

        $this->storeSettings($customer, $request->validated());
        $this->saveImage($customer, Arr::get($input, 'order_slip_logo'), 'order_slip_logo');

        return $customer;
    }

    public function storeBatch(StoreBatchRequest $request)
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $storeRequest = StoreRequest::make($record);
            $responseCollection->add($this->store($storeRequest));
        }

        return $responseCollection;
    }

    public function update(FormRequest $request, Customer $customer)
    {
        $input = $request->validated();

        if (!empty(Arr::get($input, 'contact_information'))) {
            $customer->contactInformation->update(Arr::get($input, 'contact_information'));
        }

        if (!empty(Arr::get($input, 'order_slip_logo'))) {
            $this->saveImage($customer, Arr::get($input, 'order_slip_logo'), 'order_slip_logo');
        }

        if (!empty(Arr::get($input, 'rate_cards'))) {
            $updateCardsRequest = UpdateRateCardsRequest::make(Arr::get($input, 'rate_cards'));
            $this->updateRateCards($updateCardsRequest, $customer);
        }

        if (!empty(Arr::get($input, 'threepl_logo'))) {
            $this->saveImage($customer, Arr::get($input, 'threepl_logo'), 'threepl_logo');
        }

        if (!empty(Arr::get($input, 'store_logo'))) {
            $this->saveImage($customer, Arr::get($input, 'store_logo'), 'store_logo');
        }

        if (!empty(Arr::get($input, 'banner_image')) && Arr::get($input, 'banner_image') != 'undefined') {
            foreach (Arr::get($input, 'banner_image') as $img) {
                $this->saveImage($customer, $img, 'banner_image');
            }
        }

        $customer->allow_child_customers = Arr::get($input, 'allow_child_customers') === '1';

        if (Arr::get($input, 'ship_from_contact_information_id') == 'none') {
            $input['ship_from_contact_information_id'] = null;
        }

        if (Arr::get($input, 'return_to_contact_information_id') == 'none') {
            $input['return_to_contact_information_id'] = null;
        }

        $customer->update($input);
        $this->storeSettings($customer, $request->validated());

        return $customer;
    }

    public function updateBatch(UpdateBatchRequest $request): Collection
    {
        $responseCollection = new Collection();

        $input = $request->validated();

        foreach ($input as $record) {
            $updateRequest = UpdateRequest::make($record);
            $customer = Customer::where('id', $record['id'])->first();

            if ($customer) {
                $responseCollection->add($this->update($updateRequest, $customer));
            }
        }

        return $responseCollection;
    }

    public function destroy(DestroyRequest $request = null, Customer $customer = null)
    {
        if (!is_null($customer)) {
            $customer->delete();

            return ['id' => $customer->id, 'name' => $customer->contactInformation->name];
        }
    }

    public function destroyBatch(DestroyBatchRequest $request)
    {
        $responseCollection = new Collection();
        $input = $request->validated();

        foreach ($input as $record) {
            $destroyRequest = DestroyRequest::make($record);
            $customer = Customer::where('id', $record['id'])->first();

            $responseCollection->add($this->destroy($destroyRequest, $customer));
        }

        return $responseCollection;
    }

    public function detachUser(Customer $customer, User $user)
    {
        return $customer->users()->detach($user->id);
    }

    public function updateUsers(UpdateUsersRequest $request, Customer $customer)
    {
        $customerUserRoles = [];

        foreach ($request->input('customer_user', []) as $customerUser) {
            $customerUserRoles[$customerUser['user_id']] = [
                'role_id' => $customerUser['role_id'],
                'warehouse_id' => Arr::get($customerUser, 'warehouse_id')
            ];
        }

        if ($newCustomerUserId = $request->input('new_user_id')) {
            $customerUserRoles[$newCustomerUserId] = [
                'role_id' => $request->get('new_user_role_id') || UserRole::ROLE_DEFAULT,
                'warehouse_id' => $request->get('new_user_warehouse_id')
            ];
        }

        $customer->users()->syncWithoutDetaching($customerUserRoles);

        return CustomerUser::where('customer_id', $customer->id)->get();
    }

    public function filterUsers(Request $request, Customer $customer): JsonResponse
    {
        $term = $request->get('term');
        $results = [];

        if ($term) {
            // TODO: sanitize term
            $term = $term . '%';

            $users = User::where('system_user', false)->whereDoesntHave('customers', fn (Builder $query) => $query->where('customer_id', $customer->id))
                ->where(static function (Builder $query) use ($term) {
                    $query->where('email', 'like', $term)
                        ->orWhereHas('contactInformation', static function (Builder $query) use ($term) {
                            $query->where('name', 'like', $term)
                                ->orWhere('company_name', 'like', $term)
                                ->orWhere('email', 'like', $term)
                                ->orWhere('zip', 'like', $term)
                                ->orWhere('city', 'like', $term)
                                ->orWhere('phone', 'like', $term);
                        });
                })
                ->get();

            foreach ($users as $user) {
                $results[] = [
                    'id' => $user->id,
                    'text' => collect([$user->contactInformation->name, $user->email])->join(', ')
                ];
            }
        }

        return response()->json([
            'results' => $results
        ]);
    }

    /**
     * @param Customer $customer
     * @return JsonResponse
     */
    public function getDimensionUnits(Customer $customer): JsonResponse
    {
        $dimensionsUnit = customer_settings($customer->id, CustomerSetting::CUSTOMER_SETTING_DIMENSIONS_UNIT, Customer::DIMENSION_UNIT_DEFAULT);
        $weightUnit = customer_settings($customer->id, CustomerSetting::CUSTOMER_SETTING_WEIGHT_UNIT, Customer::WEIGHT_UNIT_DEFAULT);

        return response()->json([
            'results' => [
                'dimension' => Customer::DIMENSION_UNITS[$dimensionsUnit],
                'weight' => Customer::WEIGHT_UNITS[$weightUnit],
                'currency' => customer_settings($customer->id, 'currency') ?? 'USD'
            ]
        ]);
    }

    public function getUserCustomers(User $user)
    {
        return $user->customers;
    }

    public function storeSettings(Customer $customer, $settings)
    {
        foreach ($settings as $setting => $value) {
            if (in_array($setting, CustomerSetting::CUSTOMER_SETTING_KEYS)) {
                CustomerSetting::updateOrCreate(
                    ['customer_id' => $customer->id, 'key' => $setting],
                    ['value' => $value]
                );

                if (!($value instanceof UploadedFile)) {
                    Cache::put('customer_setting_' . $customer->id . '_' . $setting, $value);
                }
            }
        }
    }

    private function createPrimaryWarehouse($contactInformation, Customer $customer): void
    {
        $warehouse = new Warehouse();
        $warehouse->customer()->associate($customer);
        $warehouse->saveQuietly();

        $contactInformation['name'] = Customer::PRIMARY_WAREHOUSE_NAME;
        $this->createContactInformation($contactInformation, $warehouse);
    }

    public function saveImage(Customer $customer, $logo, $imageType): void
    {
        if ($logo instanceof UploadedFile) {
            $filename = $logo->store('public/customers');
            $source = url(Storage::url($filename));

            $imageObj = new Image();
            $imageObj->source = $source;
            $imageObj->filename = $filename;
            $imageObj->object_id = $customer->id;
            $imageObj->object_type = Customer::class;
            $imageObj->image_type = $imageType;
            $imageObj->save();
        }
    }

    public function getShippingMethods(Request $request, ShippingCarrier $shippingCarrier)
    {
        $results = [];

        foreach ($shippingCarrier->shippingMethods as $method) {
            $results[] = [
                'id' => $method->id,
                'text' => $method->name
            ];
        }

        return response()->json([
            'results' => $results
        ]);
    }

    /**
     * @param UpdateRateCardsRequest $request
     * @param Customer $customer
     * @return array
     */
    public function updateRateCards(UpdateRateCardsRequest $request, Customer $customer): array
    {
        $input = $request->validated();
        $customer->rateCards()->detach();

        if (array_key_exists('primary_rate_card_id', $input) && !empty($input['primary_rate_card_id'])) {
            $customer->rateCards()->attach(
                $input['primary_rate_card_id'],
                ['priority' => RateCard::PRIMARY_RATE_CARD_PRIORITY]
            );
        }

        if (array_key_exists('secondary_rate_card_id', $input) && !empty($input['secondary_rate_card_id'])) {
            $customer->rateCards()->attach(
                $input['secondary_rate_card_id'],
                ['priority' => RateCard::SECONDARY_RATE_CARD_PRIORITY]
            );
        }

        return $input;
    }
}
