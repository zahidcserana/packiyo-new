<?php

use App\Models\{Customer, User};
use Behat\Behat\Tester\Exception\PendingException;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\NewAccessToken;
use App\Models\Automation;

/**
 * Most use cases should be scoped to a customer or user - this trait helps with that.
 */
trait HasScope
{
    protected User|null $user = null;

    protected Customer|null $customer = null;

    protected NewAccessToken|null $token = null;

    protected TestResponse|null $response = null;

    protected Automation|null $automation = null;

    public function setAutomationInScope(Automation $automation): void
    {
        $this->$automation = $automation;
    }

    public function setUserInScope(User $user): void
    {
        $this->user = $user;
    }

    public function setCustomerInScope(Customer $customer): void
    {
        $this->customer = $customer;
    }

    public function setTokenInScope(NewAccessToken $token): void
    {
        $this->token = $token;
    }

    public function setResponseInScope(TestResponse $response): void
    {
        $this->response = $response;
    }

    public function hasUserInScope(): bool
    {
        return !is_null($this->user);
    }

    public function hasCustomerInScope(): bool
    {
        return !is_null($this->customer);
    }

    public function hasTokenInScope(): bool
    {
        return !is_null($this->token);
    }

    public function hasResponseInScope(): bool
    {
        return !is_null($this->response);
    }

    public function getAutomationInScope(): Automation
    {
        if (is_null($this->automation)) {
            throw new PendingException("TODO: No Automation is in scope yet.");
        }

        return $this->automation;
    }

    public function getUserInScope(): User
    {
        if (is_null($this->user)) {
            throw new PendingException("TODO: No user is in scope yet.");
        }

        return $this->user;
    }

    public function getCustomerInScope(): Customer
    {
        if (is_null($this->customer)) {
            throw new PendingException("TODO: No customer is in scope yet.");
        }

        return $this->customer;
    }

    public function getTokenInScope(): NewAccessToken
    {
        if (is_null($this->token)) {
            throw new PendingException("TODO: No API token is in scope yet.");
        }

        return $this->token;
    }

    public function getResponseInScope(): TestResponse
    {
        if (is_null($this->response)) {
            throw new PendingException("TODO: No HTTP response is in scope yet.");
        }

        return $this->response;
    }
}
