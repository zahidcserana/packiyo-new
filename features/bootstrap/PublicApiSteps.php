<?php

use App\Models\Automation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use Behat\Gherkin\Node\TableNode;
use Behat\Gherkin\Node\PyStringNode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Testing\Fluent\AssertableJson;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Core\Exceptions\JsonApiException;

/**
 * Behat steps to test the Public API.
 */
trait PublicApiSteps
{
    use MakesJsonApiRequests;

    protected array $dataAttributes = [];

    /**
     * @Given I call the customer endpoint filtering by :customerName parent
     */
    public function iCallTheCustomerEndpointFilteringByParent(string $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $response = $this->getJson('/api/frontendv1/customers?filter[parent-id]='.$customer->id, [
            'Accept' => 'application/vnd.api+json',
            'Authorization' => 'Bearer ' . $this->getTokenInScope()->plainTextToken
        ]);

        $this->setResponseInScope($response);
    }

    /**
     * @Given I call the :path endpoint
     */
    public function iCallTheEndpoint(string $path): void
    {
        if (!defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        $response = $this->getJson($path, [
            'Accept' => 'application/vnd.api+json',
            'Authorization' => 'Bearer ' . $this->getTokenInScope()->plainTextToken
        ]);

        $this->setResponseInScope($response);
    }

    /**
     * @Given the placeholder :placeholder is the ID of the customer :customerName
     */
    public function thePlaceholderCustomeridIsTheIdOfTheCustomer(string $placeholder, string $customerName): void
    {
        $customer = Customer::whereHas(
            'contactInformation', fn (Builder $query) => $query->where('name', $customerName)
        )->firstOrFail();

        $this->addPlaceholder($placeholder, $customer->id);
    }

    /**
     * @Given the placeholder :placeholder is the ID of the shipping method :shippingMethodName of the carrier :carrierName
     */
    public function thePlaceholderShippingmethodidIsTheIdOfTheShippingMethodOfTheCarrier(
        string $placeholder, string $shippingMethodName, string $carrierName
    ): void
    {
        $shippingMethod = ShippingMethod::whereHas(
            'shippingCarrier', fn (Builder $query) => $query->where('name', $carrierName)
        )->where('name', $shippingMethodName)->firstOrFail();

        $this->addPlaceholder($placeholder, $shippingMethod->id);
    }

    /**
     * @Given the placeholder :placeholder is the ID of the automation :automationName
     */
    public function givenThePlaceholderIsTheIdOfTheAutomation(string $placeholder, string $automationName): void
    {
        $automation = Automation::where('name', $automationName)->firstOrFail();

        $this->addPlaceholder($placeholder, $automation->id);
    }

    protected function assertNestedJsonField(string $field, callable $callback): void
    {
        $path = array_slice(array_reverse(explode('.', $field)), 1);

        foreach ($path as $key) {
            $callback = fn (AssertableJson $json) => $json->has($key, $callback)->etc();
        }

        $this->getResponseInScope()->assertJson($callback);
    }

    protected function assertNestedJsonFieldValue(string $field, mixed $value): void
    {
        $key = array_slice(explode('.', $field), -1)[0];
        $callback = fn (AssertableJson $json) => $json->where($key, $value)->etc();

        $this->assertNestedJsonField($field, $callback);
    }

    protected function assertNestedJsonFieldType(string $field, string $type): void
    {
        $key = array_slice(explode('.', $field), -1)[0];
        $callback = fn (AssertableJson $json) => $json->whereType($key, $type)->etc();

        $this->assertNestedJsonField($field, $callback);
    }

    protected function assertNestedJsonFieldMissing(string $field): void
    {
        $key = array_slice(explode('.', $field), -1)[0];
        $callback = fn (AssertableJson $json) => $json->missing($key)->etc();

        $this->assertNestedJsonField($field, $callback);
    }

    /**
     * @Then the response contains the field :field with the value :value
     */
    public function theResponseContainsTheFieldWithTheValue(mixed $field, string $value): void
    {
        $this->assertNestedJsonFieldValue($field, $value);
    }

    /**
     * @Then the response contains the number field :field with the value :value
     */
    public function theResponseContainsTheNumberFieldWithTheValue(mixed $field, string $value): void
    {
        $this->assertNestedJsonFieldValue($field, parse_int_or_float($value));
    }

    /**
     * @Then the response contains the Boolean field :field with the value :value
     */
    public function theResponseContainsTheBooleanFieldWithTheValue(string $field, string $value): void
    {
        $this->assertNestedJsonFieldValue($field, filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }

    /**
     * @Then the response contains the field :field with the ID of the user :email
     */
    public function theResponseContainsTheFieldWithTheIdOfTheUser(string $field, string $email): void
    {
        $user = User::where('email', $email)->firstOrFail();
        $this->assertNestedJsonFieldValue($field, (string) $user->id);
    }

    /**
     * @Then the response contains the field :field with a reference to the customer :customerName
     */
    public function theResponseContainsTheFieldWithAReferenceToTheCustomer(string $field, string $customerName)
    {
        $customer = Customer::whereHas(
            'contactInformation',
            fn (Builder $query) => $query->where('name', $customerName)
        )->firstOrFail();

        $this->assertNestedJsonFieldValue($field, [['type' => 'customers', 'id' => (string) $customer->id]]);
    }

    /**
     * @Then the response contains the field :field with an empty list
     */
    public function theResponseContainsTheFieldWithAnEmptyList(string $field): void
    {
        $this->assertNestedJsonFieldValue($field, []);
    }

    /**
     * @Then the response does not contain the field :field
     */
    public function theResponseDoesNotContainTheField(string $field): void
    {
        $this->assertNestedJsonFieldMissing($field);
    }

    /**
     * @Then the response contains the text field :field
     */
    public function theResponseContainsTheTextField(string $field): void
    {
        $this->assertNestedJsonFieldType($field, 'string');
    }

    /**
     * @Then the response contains the object field :field
     */
    public function theResponseContainsTheObjectField(string $field): void
    {
        $this->assertNestedJsonFieldType($field, 'array');
    }

    /**
     * @Then the response contains the field :field with the values
     */
    public function theResponseContainsTheFieldWithTheValues(string $field, TableNode $valuesTable)
    {
        $this->assertNestedJsonFieldValue($field, $valuesTable->getRow(0));
    }

    /**
     * @Then the response contains the array field :field with the value :value
     */
    public function theResponseContainsTheArrayFieldWithTheValue(mixed $field, string $value): void
    {
        $this->assertNestedJsonFieldValue($field, json_decode($value,true));
    }

    /**
     * @Then the response is paginated
     */
    public function theResponseIsPaginated(): void
    {
        $this->assertNestedJsonFieldType('meta.page', 'array');
        $this->assertNestedJsonFieldType('meta.page.perPage', 'integer');
        $this->assertNestedJsonFieldType('meta.page.currentPage', 'integer');
        $this->assertNestedJsonFieldType('meta.page.lastPage', 'integer');
        $this->assertNestedJsonFieldType('meta.page.from', 'integer');
        $this->assertNestedJsonFieldType('meta.page.to', 'integer');
        $this->assertNestedJsonFieldType('meta.page.total', 'integer');
    }

    /**
     * @When the customer :customerName gets the order number :orderNumber from the Public API for these SKUs
     */
    public function theCustomerGetsTheOrderNumberFromThePublicApiForTheseSkus(
        string $customerName, string $orderNumber, TableNode $itemsTable
    ): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
        $order = Order::factory()->make([
            'customer_id' => $customer->id,
            'number' => $orderNumber
        ]);
        $orderItems = collect($itemsTable->getRows())->map(function (array $row) use ($customer, $order) {
            [$quantity, $sku] = $row;
            $product = Product::withTrashed()->where(['customer_id' => $customer->id, 'sku' => $sku])->firstOrFail();

            return OrderItem::factory()->make([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price,
                'quantity_shipped' => 0
            ]);
        });

        $response = $this->postJson('/api/v1/orders', [
            'data' => [
                'type' => 'orders',
                'attributes' => [
                    'number' => $order->number,
                    'ordered_at' => $order->ordered_at->format('Y-m-d H:i:s'),
                    // 'external_id' => $order->external_id,
                    'external_id' => 'external_' . $order->number,
                    'shipping_contact_information_data' => array_merge(
                        $order->shippingContactInformation->toArray(),
                        ['country' => $order->shippingContactInformation->country->iso_3166_2]
                    ),
                    'billing_contact_information_data' => array_merge(
                        $order->billingContactInformation->toArray(),
                        ['country' => $order->billingContactInformation->country->iso_3166_2]
                    ),
                    'order_item_data' => $orderItems->map(fn (OrderItem $item) => [
                        'sku' => $item->product->sku,
                        'quantity' => $item->quantity,
                        // 'external_id' => $item->external_id,
                        'external_id' => 'external_' . $item->product->sku,
                        'price' => $item->price
                    ])->toArray()
                ],
                'relationships' => [
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id' => (string) $customer->id,
                        ],
                    ],
                ],
            ],
        ], [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
            'Authorization' => 'Bearer ' . $this->getTokenInScope()->plainTextToken
        ]);

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }

    /**
     * @When the customer :customerName gets the wholesale order number :orderNumber from the Public API for these barcodes
     */
    public function theCustomerGetsTheWholesaleOrderNumberFromThePublicApiForTheseBarcodes(
        string $customerName, string $orderNumber, TableNode $itemsTable
    ): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $contactInfo = [
            'name' => 'Pete Davidson',
            'company_name' => 'Staten Island Ferry',
            'company_number' => '4',
            'address' => 'St George Ferry/S62',
            'address2' => '',
            'zip' => '10301',
            'city' => 'Staten Island',
            'state' => 'NY',
            'country' => 'US',
            'email' => 'pete@statenislandferry.com',
            'phone' => ''
        ];

        $response = $this->postJson('/api/v1/orders', [
            'data' => [
                'type' => 'orders',
                'attributes' => [
                    'is_wholesale' => true,
                    'number' => $orderNumber,
                    'ordered_at' => now()->format('Y-m-d H:i:s'),
                    'external_id' => 'external_' . $orderNumber,
                    'shipping_contact_information_data' => $contactInfo,
                    'billing_contact_information_data' => $contactInfo,
                    'order_item_data' => collect($itemsTable->getRows())->map(function (array $row) use ($customer) {
                        [$quantity, $barcode] = $row;

                        return [
                            'barcode' => $barcode,
                            'quantity' => $quantity,
                            'external_id' => 'external_' . $barcode,
                            'price' => 1.0
                        ];
                    })->toArray()
                ],
                'relationships' => [
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id' => (string) $customer->id,
                        ],
                    ],
                ],
            ],
        ], [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
            'Authorization' => 'Bearer ' . $this->getTokenInScope()->plainTextToken,
        ]);

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }

    /**
     * @When the customer :customerName gets the order number :orderNumber from the Public API without product ID with SKU :sku and quantity :quantity
     */
    public function theCustomerGetsTheOrderNumberFromThePublicApiWithoutProductIdWithSkuAndQuantity(
        string $customerName,
        string $orderNumber,
        string $sku,
        string $quantity
    ): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $order = Order::factory()->make([
            'customer_id' => $customer->id,
            'number' => $orderNumber
        ]);

        $response = $this->postJson('/api/v1/orders', [
            'data' => [
                'type' => 'orders',
                'attributes' => [
                    'number' => $order->number,
                    'ordered_at' => $order->ordered_at->format('Y-m-d H:i:s'),
                    // 'external_id' => $order->external_id,
                    'external_id' => 'external_' . $order->number,
                    'shipping_contact_information_data' => array_merge(
                        $order->shippingContactInformation->toArray(),
                        ['country' => $order->shippingContactInformation->country->iso_3166_2]
                    ),
                    'billing_contact_information_data' => array_merge(
                        $order->billingContactInformation->toArray(),
                        ['country' => $order->billingContactInformation->country->iso_3166_2]
                    ),
                    'order_item_data' => [
                        [
                            "product_id" => null,
                            "sku" => $sku,
                            "quantity" => $quantity,
                            "price" => 500
                        ],
                    ],
                    'tags' => 'orderTag1, orderTag2'
                ],
                'relationships' => [
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id' => (string) $customer->id,
                        ],
                    ],
                ],
            ]
        ], [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
            'Authorization' => 'Bearer ' . $this->getTokenInScope()->plainTextToken,
        ]);

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }

    /**
     * @When I Store the link :link called :linkName and is printable :isPrintable and the printer type is :printerType to the Shipment using public API
     */
    public function IStoreTheLinkToTheShipmentUsingPublicApi(string $link, string $linkName, string $isPrintable, string $printerType): void
    {
        $response = $this->postJson('/api/v1/links', [
            'data' => [
                'type' => 'links',
                'attributes' => [
                    "name" => $linkName,
                    "url" => $link,
                    "is_printable" => filter_var($isPrintable, FILTER_VALIDATE_BOOLEAN),
                    "printer_type" => $printerType,
                ],
                'relationships' => [
                    'shipment' => [
                        'data' => [
                            'type' => 'shipments',
                            'id' => (string) $this->getShipmentInScope()->id,
                        ],
                    ],
                ],
            ]
        ], [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
            'Authorization' => 'Bearer ' . $this->getTokenInScope()->plainTextToken,

        ]);

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }

    /**
     * @When I post this to the :path endpoint
     */
    public function iPostThisToTheEndpoint(string $path, PyStringNode $payload): void
    {
        $payload = $this->renderWithPlaceholders($payload);
        $data = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Error decoding JSON: ' . json_last_error_msg());
        }

        $response = $this->postJson($path, (array) $data, [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
            'Authorization' => 'Bearer ' . $this->getTokenInScope()->plainTextToken,
        ]);

        if ($response->getStatusCode() === 500 || $response->exception instanceof JsonApiException) {
            Log::error($response->exception, context: [$response->getContent()]);
            throw $response->exception;
        }

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }

    /**
     * @When I store the external carrier credential using public API
     */
    public function IStoreTheExternalCarrierCredentialUsingPublicApi(): void
    {
        $customer = $this->getCustomerInScope();

        $response = $this->postJson('/api/v1/external-carrier-credentials', [
            'data' => [
                'type' => 'external-carrier-credentials',
                'attributes' => $this->dataAttributes,
                'relationships' => [
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id' => (string) $customer->id,
                        ],
                    ],
                ],
            ]
        ], [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
            'Authorization' => 'Bearer ' . $this->getTokenInScope()->plainTextToken,

        ]);

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }


    /**
     * @Then I store the purchase order using public API
     */
    public function IStoreThePurchaseOrderUsingPublicApi(): void
    {
        $customer = $this->getCustomerInScope();

        $this->dataAttributes['ordered_at'] = Carbon::now()->format('Y-m-d H:i:d');
        $this->dataAttributes['expected_at'] = Carbon::now()->addDays(7)->format('Y-m-d H:i:d');

        $response = $this->postJson('/api/v1/purchase-orders', [
            'data' => [
                'type' => 'purchase-orders',
                'attributes' => $this->dataAttributes,
                'relationships' => [
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id' => (string) $customer->id,
                        ],
                    ],
                ],
            ],
        ], [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
            'Authorization' => 'Bearer ' . $this->getTokenInScope()->plainTextToken,
        ]);

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }

    /**
     * @When I update the external carrier credentials using public API
     */
    public function IUpdateTheExternalCarrierCredentialsUsingPublicApi(): void
    {
        $customer = $this->getCustomerInScope();
        $externalCarrierCredentialInScope = $this->externalCarrierCredentialInScope;

        $response = $this->patchJson('/api/v1/external-carrier-credentials/' . $externalCarrierCredentialInScope->id, [
            'data' => [
                'type' => 'external-carrier-credentials',
                'id' => (string) $externalCarrierCredentialInScope->id,
                'attributes' => $this->dataAttributes,
                'relationships' => [
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id' => (string) $customer->id,
                        ],
                    ],
                ],
            ]
        ], [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
                'Authorization' => 'Bearer ' . $this->getTokenInScope()->plainTextToken,
        ]);

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }

    /**
     * @When I store the product using public API
     */
    public function IStoreTheProductUsingPublicApi(): void
    {
        $customer = $this->getCustomerInScope();

        $response = $this->postJson('/api/v1/products', [
            'data' => [
                'type' => 'products',
                'attributes' => $this->dataAttributes,
                'relationships' => [
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id' => (string) $customer->id,
                        ],
                    ],
                ],
            ],
        ], [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
            'Authorization' => 'Bearer ' . $this->getTokenInScope()->plainTextToken,
        ]);

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }

    /**
     * @When I delete the external carrier credential using public API
     */
    public function theCustomerDeletesExternalCarrierCredentialUsingPublicApi(): void
    {

        $customer = $this->getCustomerInScope();
        $externalCarrierCredentialInScope = $this->externalCarrierCredentialInScope;

        $response = $this->deleteJson('/api/v1/external-carrier-credentials/' . $externalCarrierCredentialInScope->id, [
            'data' => [
                'type' => 'external-carrier-credentials',
                'id' => (string) $externalCarrierCredentialInScope->id,
                'attributes' => $this->dataAttributes,
                'relationships' => [
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id' => (string) $customer->id,
                        ],
                    ],
                ],
            ]
        ], [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
            'Authorization' => 'Bearer ' . $this->getTokenInScope()->plainTextToken,
        ]);

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }

    /**
     * @When I update the product using public API
     */
    public function IUpdateTheProductUsingPublicApi(): void
    {
        $customer = $this->getCustomerInScope();
        $product = $this->productInScope;

        $response = $this->patchJson('/api/v1/products/' . $product->id, [
            'data' => [
                'type' => 'products',
                'id' => (string) $product->id,
                'attributes' => $this->dataAttributes,
                'relationships' => [
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id' => (string) $customer->id,
                        ],
                    ],
                ],
            ],
        ], [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
            'Authorization' => 'Bearer ' . $this->getTokenInScope()->plainTextToken,
        ]);

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }

    }

    /**
     * @When I update the purchase order using public API
     */
    public function IUpdatePurchaseOrderUsingPublicApi(): void
    {
        $customer = $this->getCustomerInScope();
        $purchaseOrder = $this->purchaseOrderInScope;

        $this->dataAttributes['ordered_at'] = Carbon::now()->format('Y-m-d H:i:d');
        $this->dataAttributes['expected_at'] = Carbon::now()->addDays(7)->format('Y-m-d H:i:d');

        $response = $this->patchJson('/api/v1/purchase-orders/'.$purchaseOrder->id, [
            'data' => [
                'type' => 'purchase-orders',
                'id' => (string) $purchaseOrder->id,
                'attributes' => $this->dataAttributes,
                'relationships' => [
                    'customer' => [
                        'data' => [
                            'type' => 'customers',
                            'id' => (string) $customer->id,
                        ],
                    ],
                ],
            ],
        ], [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
            'Authorization' => 'Bearer ' . $this->getTokenInScope()->plainTextToken,
        ]);

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }

    /**
     * @Then I pass in request body a single relationship resource :relationshipKey
     */
    public function IPassInRequestBodyASingleRelationshipResource(string $relationshipKey, TableNode $tableNode): void
    {
        $data = [];
        foreach ($tableNode->getRows() as $key => $row) {
            $data[$row[0]] = $row[1];
        }

        $this->dataAttributes[$relationshipKey][] = $data;
    }

    /**
     * @Then I pass in request body data attributes
     */
    public function IPassDataAttributes(TableNode $tableNode):void
    {
        collect($tableNode->getRows())->map(function ($row, $key) {
            $this->dataAttributes[$row[0]] = $row[1];
        });
    }

    /**
     * @Then the response contains the field :field with data count :count
     */
    public function theResponseContainsTheFieldWithDataCount(string $field, $count): void
    {
        $callback = fn (AssertableJson $json) => $json->has($field, $count)->etc();
        $this->getResponseInScope()->assertJson($callback);
    }

    /**
     * @Then the response error with field :field contains error message
     */
    public function theResponseErrorWithFieldContainsErrorMessage(string $field, PyStringNode $message): void
    {
        $path = array_reverse(explode('.', $field));
        $key = array_shift($path);
        $callback = fn (AssertableJson $json) => $json->where($key, $message->getRaw())->etc();

        foreach ($path as $key) {
            $callback = fn (AssertableJson $json) => $json->has($key, $callback)->etc();
        }

        $this->getResponseInScope()->assertJson($callback);
    }

    /**
     * @Then the response error with errors field :field contains error message
     */
    public function theResponseErrorWithRealFieldContainsErrorMessage(string $field, PyStringNode $message): void
    {
        $this->getResponseInScope()->assertJsonValidationErrors([$field => $message->getRaw()]);
    }

    /**
     * @Then the response code is :code
     */
    public function theResponseCodeIs(string $code): void
    {
        $this->getResponseInScope()->assertStatus((int) $code);
    }

    /**
     * @Then I pass in request body the data attributes
     */
    public function IPassInRequestBodyTheDataAttributes(TableNode $tableNode):void
    {
        collect($tableNode->getRows())->map(function ($row, $key) {
            $this->dataAttributes[$row[0]] = $row[1];
        });
    }

    /**
     * @When call the Automation endpoint to set the is_enabled field :isEnabled. Automation Name :automationName
     */
    public function callTheAutomationEndpointToSeIsEnabledAutomationNamed(
        string $isEnabled,
        string $automationName
    ): void {
        $automation = Automation::where('name', $automationName)->firstOrFail();

        $response = $this->patchJson('/api/frontendv1/automations/'.$automation->id,
            [
                "data" => [
                    "type" => "automations",
                    "id" => (string)$automation->id,
                    "attributes" => [
                        "is_enabled" => $isEnabled === "true"
                    ]
                ]
            ],[
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
                'Authorization' => 'Bearer ' . $this->getTokenInScope()->plainTextToken,
            ]
        );

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }

    /**
     * @When I call the :endpoint endpoint to filter child customer :customerName objects
     */
    public function iCallTheEndpointWithFilterChildCustomerObjects(string $endpoint, $customerName): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $this->iCallTheEndpoint($endpoint . '?filter[customer]=' . $customer->id);

    }
    private function generateAutomationCustomerPayload(Automation $automation, Customer $customer) : array
    {
        return [
            "data" => [
                [
                    "type" => "customers",
                    "id" => (string)$customer->id
                ]
            ]
        ];
    }

    private function generatePublicApiHeader()
    {
        return  [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
            'Authorization' => 'Bearer ' . $this->getTokenInScope()->plainTextToken,
        ];
    }

    /**
     * @When attach a Customer :customerName to the Automation :automationName
     */
    public function attachACustomerToTheAutomation(
        string $customerName,
        string $automationName,
    ): void {
        $automation = Automation::where('name', $automationName)->firstOrFail();

        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $response = $this->postJson("/api/frontendv1/automations/{$automation->id}/relationships/applies-to-customers",
            $this->generateAutomationCustomerPayload($automation, $customer),
            $this->generatePublicApiHeader()
        );

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }

    /**
     * @When detach a Customer :customerName to the Automation :automationName
     */
    public function detachACustomerToTheAutomation(
        string $customerName,
        string $automationName,
    ): void {
        $automation = Automation::where('name', $automationName)->firstOrFail();

        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $response = $this->deleteJson("/api/frontendv1/automations/{$automation->id}/relationships/applies-to-customers",
            $this->generateAutomationCustomerPayload($automation, $customer),
            $this->generatePublicApiHeader()
        );

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }

    /**
     * @When set only the Customer :customerName to the Automation :automationName
     */
    public function syncACustomerToTheAutomation(
        string $customerName,
        string $automationName,
    ): void {
        $automation = Automation::where('name', $automationName)->firstOrFail();

        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $response = $this->patchJson("/api/frontendv1/automations/{$automation->id}/relationships/applies-to-customers",
            $this->generateAutomationCustomerPayload($automation, $customer),
            $this->generatePublicApiHeader()
        );

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }

    /**
     * @When get the Customers from the Automation :automationName
     */
    public function getTheCustomersFromTheAutomation(
        string $automationName,
    ): void {
        $automation = Automation::where('name', $automationName)->firstOrFail();

        $response = $this->getJson("/api/frontendv1/automations/{$automation->id}/relationships/applies-to-customers",
            $this->generatePublicApiHeader()
        );

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }

    /**
     * @Then the response contains the field :field with these tags
     */
    public function theResponseContainsTheFieldWithTheseTags(mixed $field,TableNode $tagsTable): void
    {
        $this->assertNestedJsonFieldValue($field, $tagsTable->getRow(0));
    }
}
