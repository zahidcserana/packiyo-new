<?php

use App\Models\Automation;
use App\Models\Customer;
use App\Models\ShippingMethod;
use App\Models\User;
use Behat\Gherkin\Node\TableNode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Testing\Fluent\AssertableJson;
use LaravelJsonApi\Testing\MakesJsonApiRequests;

/**
 * Behat steps to test the Public API.
 */
trait PublicApiWholesaleSteps
{
    use MakesJsonApiRequests;

    protected array $dataAttributes = [];

    // /**
    //  * @Given the placeholder :placeholder is the ID of the customer :customerName
    //  */
    // public function thePlaceholderCustomeridIsTheIdOfTheCustomer(string $placeholder, string $customerName): void
    // {
    //     $customer = Customer::whereHas(
    //         'contactInformation', fn (Builder $query) => $query->where('name', $customerName)
    //     )->firstOrFail();

    //     $this->addPlaceholder($placeholder, $customer->id);
    // }

    // /**
    //  * @Given the placeholder :placeholder is the ID of the shipping method :shippingMethodName of the carrier :carrierName
    //  */
    // public function thePlaceholderShippingmethodidIsTheIdOfTheShippingMethodOfTheCarrier(
    //     string $placeholder, string $shippingMethodName, string $carrierName
    // ): void
    // {
    //     $shippingMethod = ShippingMethod::whereHas(
    //         'shippingCarrier', fn (Builder $query) => $query->where('name', $carrierName)
    //     )->where('name', $shippingMethodName)->firstOrFail();

    //     $this->addPlaceholder($placeholder, $shippingMethod->id);
    // }

    // /**
    //  * @Given the placeholder :placeholder is the ID of the automation :automationName
    //  */
    // public function givenThePlaceholderIsTheIdOfTheAutomation(string $placeholder, string $automationName): void
    // {
    //     $automation = Automation::where('name', $automationName)->firstOrFail();

    //     $this->addPlaceholder($placeholder, $automation->id);
    // }

    // protected function assertNestedJsonField(string $field, callable $callback): void
    // {
    //     $path = array_slice(array_reverse(explode('.', $field)), 1);

    //     foreach ($path as $key) {
    //         $callback = fn (AssertableJson $json) => $json->has($key, $callback)->etc();
    //     }

    //     $this->getResponseInScope()->assertJson($callback);
    // }

    // protected function assertNestedJsonFieldValue(string $field, mixed $value): void
    // {
    //     $key = array_slice(explode('.', $field), -1)[0];
    //     $callback = fn (AssertableJson $json) => $json->where($key, $value)->etc();

    //     $this->assertNestedJsonField($field, $callback);
    // }

    // protected function assertNestedJsonFieldType(string $field, string $type): void
    // {
    //     $key = array_slice(explode('.', $field), -1)[0];
    //     $callback = fn (AssertableJson $json) => $json->whereType($key, $type)->etc();

    //     $this->assertNestedJsonField($field, $callback);
    // }

    // protected function assertNestedJsonFieldMissing(string $field): void
    // {
    //     $key = array_slice(explode('.', $field), -1)[0];
    //     $callback = fn (AssertableJson $json) => $json->missing($key)->etc();

    //     $this->assertNestedJsonField($field, $callback);
    // }

    // /**
    //  * @Then the response contains the field :field with the value :value
    //  */
    // public function theResponseContainsTheFieldWithTheValue(mixed $field, string $value): void
    // {
    //     $this->assertNestedJsonFieldValue($field, $value);
    // }

    // /**
    //  * @Then the response contains the array field :field with the value :value
    //  */
    // public function theResponseContainsTheArrayFieldWithTheValue(mixed $field, string $value): void
    // {
    //     $this->assertNestedJsonFieldValue($field, json_decode($value,true));
    // }

    // /**
    //  * @Then the response contains the number field :field with the value :value
    //  */
    // public function theResponseContainsTheNumberFieldWithTheValue(mixed $field, string $value): void
    // {
    //     $this->assertNestedJsonFieldValue($field, parse_int_or_float($value));
    // }

    // /**
    //  * @Then the response contains the Boolean field :field with the value :value
    //  */
    // public function theResponseContainsTheBooleanFieldWithTheValue(string $field, string $value): void
    // {
    //     $this->assertNestedJsonFieldValue($field, filter_var($value, FILTER_VALIDATE_BOOLEAN));
    // }

    // /**
    //  * @Then the response contains the field :field with the ID of the user :email
    //  */
    // public function theResponseContainsTheFieldWithTheIdOfTheUser(string $field, string $email): void
    // {
    //     $user = User::where('email', $email)->firstOrFail();
    //     $this->assertNestedJsonFieldValue($field, (string) $user->id);
    // }

    // /**
    //  * @Then the response contains the field :field with a reference to the customer :customerName
    //  */
    // public function theResponseContainsTheFieldWithAReferenceToTheCustomer(string $field, string $customerName)
    // {
    //     $customer = Customer::whereHas(
    //         'contactInformation',
    //         fn (Builder $query) => $query->where('name', $customerName)
    //     )->firstOrFail();

    //     $this->assertNestedJsonFieldValue($field, [['type' => 'customers', 'id' => (string) $customer->id]]);
    // }

    // /**
    //  * @Then the response contains the field :field with an empty list
    //  */
    // public function theResponseContainsTheFieldWithAnEmptyList(string $field): void
    // {
    //     $this->assertNestedJsonFieldValue($field, []);
    // }

    // /**
    //  * @Then the response does not contain the field :field
    //  */
    // public function theResponseDoesNotContainTheField(string $field): void
    // {
    //     $this->assertNestedJsonFieldMissing($field);
    // }

    // /**
    //  * @Then the response contains the text field :field
    //  */
    // public function theResponseContainsTheTextField(string $field): void
    // {
    //     $this->assertNestedJsonFieldType($field, 'string');
    // }

    // /**
    //  * @Then the response contains the object field :field
    //  */
    // public function theResponseContainsTheObjectField(string $field): void
    // {
    //     $this->assertNestedJsonFieldType($field, 'array');
    // }

    // /**
    //  * @Then the response contains the field :field with the values
    //  */
    // public function theResponseContainsTheFieldWithTheValues(string $field, TableNode $valuesTable)
    // {
    //     $this->assertNestedJsonFieldValue($field, $valuesTable->getRow(0));
    // }

    // /**
    //  * @Then the response is paginated
    //  */
    // public function theResponseIsPaginated(): void
    // {
    //     $this->assertNestedJsonFieldType('meta.page', 'array');
    //     $this->assertNestedJsonFieldType('meta.page.perPage', 'integer');
    //     $this->assertNestedJsonFieldType('meta.page.currentPage', 'integer');
    //     $this->assertNestedJsonFieldType('meta.page.lastPage', 'integer');
    //     $this->assertNestedJsonFieldType('meta.page.from', 'integer');
    //     $this->assertNestedJsonFieldType('meta.page.to', 'integer');
    //     $this->assertNestedJsonFieldType('meta.page.total', 'integer');
    // }
}
