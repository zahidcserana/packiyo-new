<?php

// use App\JsonApi\V1\Users\UserResource;

// use App\Http\Resources\UserResource;

use App\Models\ExternalCarrierCredential;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Behat\Gherkin\Node\TableNode;

/**
 * Behat steps to test the Public API.
 */
trait ExternalCarrierCredentialSteps
{
    protected ExternalCarrierCredential|null $externalCarrierCredentialInScope = null;

    /**
     * @Given the customer :customerName has external carrier credential with reference :referenceName
     */
    public function theCustomerHasExternalCarrierCredentialWithReference(string $customerName, string $referenceName, $carriersUrl = ''): void
    {
        $customer = Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();

        $this->externalCarrierCredentialInScope = ExternalCarrierCredential::factory()->create([
            'customer_id' => $customer->id,
            'reference' => $referenceName,
        ]);
    }
}
