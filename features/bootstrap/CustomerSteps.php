<?php

use App\Components\BillingRates\StorageByLocationRate\MongoDbConnectionTester;
use App\Models\{
    Customer,
    AddressBook,
    ContactInformation,
    CustomerSetting,
    EasypostCredential,
    User,
    CustomerUser,
    CustomerUserRole,
    OrderChannel,
    ShippingBox,
    Supplier,
    ExternalCarrierCredential,
    Currency,
    UserSetting,
    Printer,
    Warehouse};
use Illuminate\Database\Eloquent\Builder;
use Behat\Gherkin\Node\TableNode;
use Laravel\Pennant\Feature;
use Mockery\MockInterface;

/**
 * Behat steps to test customers.
 */
trait CustomerSteps
{
    /**
     * @Given the customer :customerName has the feature flag :featureFlag on
     */
    public function theCustomerHasTheFeatureFlagOn(string $customerName, string $featureFlag): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        Feature::for($customer)->activate($featureFlag);
    }
    /**
     * @Given the customer :customerName has the feature flag :featureFlag off
     */
    public function theCustomerHasTheFeatureFlagOff(string $customerName, string $featureFlag): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        Feature::for($customer)->deactivate($featureFlag);
    }

    /**
     * @Given the DocumentDb service is not working
     */
    public function theDocumentDbServiceIsNotWorking(): void
    {
        // TODO check if we should improve this. We would need to mock DB or MongoDB\Database listCollections call and throw.
        // I couldn't mock the MongoDB\Database listCollections call, so I mocked the MongoDbConnectionTester class.
        $this->mock(MongoDbConnectionTester::class, function (MockInterface $mock) {
            $mock->shouldReceive('testConnection')->andReturn(false);
        });
    }

    /**
     * @Given a customer called :customerName based in :countryName
     */
    public function aCustomerCalledBasedIn(string $customerName, string $countryName): void
    {
        $country = \Countries::where('name', $countryName)->firstOrFail();
        $customer = Customer::factory()->create(['allow_child_customers' => false]);
        ContactInformation::factory()->create([
            'object_type' => Customer::class,
            'object_id' => $customer->id,
            'country_id' => $country->id,
            'name' => $customerName
        ]);

        $addressBook = AddressBook::factory()->create([
            'customer_id' => $customer->id,
            'name' => $customerName
        ]);

        $contactInformation = ContactInformation::factory()->create([
            'object_type' => AddressBook::class,
            'object_id' => $addressBook->id,
            'country_id' => $country->id,
            'name' => $customerName
        ]);

        $customer->ship_from_contact_information_id = $contactInformation->id;
        $customer->return_to_contact_information_id = $contactInformation->id;
        $customer->save();

        $this->defineCustomerScope($customer);
    }

    /**
     * @Given the customer :customerName has a default currency
     */
    public function theCustomerDefaultCurrencyIs(string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        // Default USD currency
        $currency = Currency::factory()->create([
            'name' => 'United States Dollar',
            'code' => 'USD',
            'symbol' => '$',
            'exchange_rate' => 1,
            'active' => 1,
        ]);
        CustomerSetting::create([
            'customer_id' => $customer->id,
            'key' => CustomerSetting::CUSTOMER_SETTING_CURRENCY,
            'value' => $currency->id,
        ]);
    }

    protected function defineCustomerScope(Customer $customer): void
    {
        if (
            method_exists($this, 'hasCustomerInScope')
            && !$this->hasCustomerInScope()
            && method_exists($this, 'setCustomerInScope')
        ) {
            $this->setCustomerInScope($customer);
        }
    }

    /**
     * @Given the user :userEmail belongs to the customer :customerName
     */
    public function theUserBelongsToTheCustomer(string $userEmail, string $customerName): void
    {
        $user = User::where('email', $userEmail)->firstOrFail();

        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $warehouse = Warehouse::whereCustomerId($customer->id)->latest('created_at')->first();

        CustomerUser::create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'role_id' => CustomerUserRole::ROLE_CUSTOMER_MEMBER,
            'warehouse_id' => $warehouse->id ?? null
        ]);
    }

    /**
     * @Given the session customer is set to :customerName
     */
    public function theSessionCustomerIsSetTo(string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        App::make(\App\Components\UserComponent::class)->setSessionCustomer($customer);
    }

    /**
     * @Given the customer :customerName has a sales channel named :channelName
     */
    public function theCustomerHasASalesChannelNamed(string $customerName, string $channelName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        OrderChannel::factory()->create(['customer_id' => $customer->id, 'name' => $channelName]);
    }

    /**
     * @Given the customer :customerName has the setting :settingKey set to :settingValue
     */
    public function theCustomerHasTheSettingSetTo(string $customerName, string $settingKey, string $settingValue): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        CustomerSetting::factory()->create([
            'customer_id' => $customer->id,
            'key' => $settingKey,
            'value' => $settingValue,
        ]);
    }

    /**
     * @Given the customer :customerName has a shipping box named :boxName
     */
    public function theCustomerHasAShippingBoxNamed(string $customerName, string $boxName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        ShippingBox::factory()->create(['customer_id' => $customer->id, 'name' => $boxName]);
    }

    /**
     * @Given the customer :customerName has a shipping box named :boxName with cost :cost
     */
    public function theCustomerHasAShippingBoxNamedWithCost(string $customerName, string $boxName, string $cost): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $box = ShippingBox::where(['customer_id' => $customer->id, 'name' => $boxName])->firstOrFail();
        $box->cost = (float)$cost;
        $box->save();
    }

    /**
     * @Given the customer :customerName has a supplier :supplierName
     */
    public function theCustomerHasASupplier(string $customerName, string $supplierName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $supplier = Supplier::factory()->create(['customer_id' => $customer->id]);

        ContactInformation::factory()->create([
            'name' => $supplierName,
            'company_name' => $customer->contactInformation->company_name,
            'company_number' => '',
            'address' => '',
            'address2' => '',
            'zip' => '',
            'city' => '',
            'email' => $customer->contactInformation->email,
            'phone' => '',
            'object_type' => get_class($supplier),
            'object_id' => $supplier->id
        ]);
    }

    /**
     * @When I will work with customer :customerName
     */
    public function iWillWorkWithCustomer($customerName)
    {
        $customer = Customer::whereHas('contactInformation', fn (Builder $query) => $query->where('name', $customerName))
            ->firstOrFail();
        $this->setCustomerInScope($customer);
    }

    /**
     * @Then I expect to have :countShippingCarrier shipping carriers with :countShippingMethod shipping methods
     */
    public function IShouldHaveShippingCarriersWithShippingMethods($countShippingCarrier, $countShippingMethod): void
    {
        $customer = $this->getCustomerInScope();

        $customer->load(
            'externalCarrierCredentials',
            'externalCarrierCredentials.shippingCarriers',
            'externalCarrierCredentials.shippingCarriers.shippingMethods'
        );

        $externalCarrierCredential = $customer->externalCarrierCredentials->first();
        $shippingCarrier = $externalCarrierCredential->shippingCarriers->first();
        $countShippingCarriers = $externalCarrierCredential->shippingCarriers->count();
        $countShippingMethods = $shippingCarrier->shippingMethods->count();

        $this->assertEquals($countShippingCarrier, $countShippingCarriers);
        $this->assertEquals($countShippingMethod, $countShippingMethods);

    }

    /**
     * @Then the customer has created external carrier credentials
     */
    public function theCustomerHasCreatedExternalCarrierCredentials(TableNode $tableNode): void
    {
        $customer = $this->getCustomerInScope();

        $fields = $tableNode->getRow(0);
        $values = $tableNode->getRow(1);
        $attributes = static::prepareAttributes($fields, $values);
        ExternalCarrierCredential::factory()->create($attributes + ['customer_id' => $customer->id]);
    }

    /**
     * @Then the customer has created Easypost credentials
     */
    public function theCustomerHasCreatedEasypostCredentials(): void
    {
        $customer = $this->getCustomerInScope();

        // Sample Easypost data
        $attributes = [
            'api_key' => env('EASYPOST_API_KEY'), // Prod API Key, necessary for creating carriers
            'test_api_key' => 'EZTK0e5cf5ba34414ca3858609f55f836d07oD2vcIqA6P22a328HVvIRA', // Test API Key
            'use_native_tracking_urls' => 0,
            'commercial_invoice_signature' => 'None',
            'commercial_invoice_letterhead' => 'None',
            'endorsement' => 0,
        ];

        static::record(fn () => EasypostCredential::create($attributes + ['customer_id' => $customer->id]));
    }

    /**
     * @param array $fields
     * @param array $values
     * @return array
     */
    public static function prepareAttributes(array $fields, array $values): array
    {
        $data = [];

        foreach ($fields as $key => $field) {
            $data[$field] = $values[$key];
        }

        return $data;
    }

    /**
     * @Given the customer :customerName has :featureFlag feature turned off
     */
    public function theCustomerHasFeatureTurnedOff($customerName, $featureFlag): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })
            ->firstOrFail();

        Feature::for($customer)->deactivate($featureFlag);
    }

    /**
     * @When the customer :customerName updates the setting :settingKey set to :settingValue
     */
    public function theCustomerUpdatesTheSettingSetToFalse($customerName, $settingKey, $settingValue): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })
            ->firstOrFail();

        CustomerSetting::updateOrCreate(
            ['customer_id' => $customer->id, 'key' => $settingKey],
            ['value' => $settingValue]
        );
    }
    /**
     * @Then customer :customerName has the :featureFlag feature enabled
     */
    public function customerHasTheFeatureEnabled($customerName, $featureFlag)
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $this->assertTrue(Feature::for($customer)->active($featureFlag));
    }

    /**
     * @Then customer :customerName has the :featureFlag feature disabled
     */
    public function customerHasTheFeatureDisabled($customerName, $featureFlag)
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $this->assertTrue(Feature::for($customer)->inactive($featureFlag));
    }

    /**
     * @When customer :customerName has the Printer type :printer_type called :printer
     */
    public function customerHasThePrinterTypeCalled($customerName,$printer_type, $printer)
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $printer = Printer::create([
            'customer_id' => $customer->id,
            'name' => $printer,
            'localhost' => 'localhost'
        ]);

        UserSetting::create([
            'user_id' => auth()->user()->id,
            'key' => $printer_type,
            'value' => $printer->id
        ]);
    }
}
