<?php

namespace App\Models\EDI\Providers;

use App\Models\Customer;
use App\Models\EDIProvider;
use Parental\HasParent;
use stdClass;

class CrstlEDIProvider extends EDIProvider
{
    use HasParent;

    protected $fillable = [
        'access_token',
        'refresh_token',
        'access_token_expires_at',
        'is_multi_crstl_org',
        'external_role',
        'external_organization_id',
        'is_sandbox'
    ];

    protected $dates = [
        'access_token_expires_at'
    ];

    protected $casts = [
        'is_multi_crstl_org' => 'bool',
        'is_sandbox' => 'bool'
    ];

    protected $attributes = [
        'is_multi_crstl_org' => false,
        'is_sandbox' => false
    ];

    static function fromLogin(Customer $ownerCustomer, string $name, stdClass $loginData): self
    {
        $provider = new self([
            'access_token' => $loginData->access_token,
            'refresh_token' => $loginData->refresh_token,
            'access_token_expires_at' => $loginData->access_token_expires_at,
            'is_multi_crstl_org' => $loginData->is_multi_org,
            'external_role' => $loginData->role,
            'external_organization_id' => $loginData->organization_id,
            'name' => $name,
            'active' => true
        ]);

        $provider->customer()->associate($ownerCustomer);

        return $provider;
    }
}
