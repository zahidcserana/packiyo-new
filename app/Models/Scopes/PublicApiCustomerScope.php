<?php

namespace App\Models\Scopes;

use App\Models\Automation;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PublicApiCustomerScope implements Scope
{
    /**
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $user = request()->user();

        // Should not be applied for Co-Pilot user
        if ($user->email != Automation::AUTOMATION_USER_EMAIL) {
            $accessToken = request()->user()->currentAccessToken();

            if ($accessToken) {
                $customer = null;

                if ($accessToken->customer_id) {
                    $customer = Customer::find($accessToken->customer_id);
                }

                if ($customer) {
                    if ($customer->is3pl()) {
                        $customers = collect($customer->children)->add($customer);

                        $builder->whereHas(
                            'customer',
                            fn (Builder $builder) => $builder->whereIn('customers.id', $customers->pluck('id'))
                        );
                    } else {
                        $builder->whereHas(
                            'customer',
                            fn (Builder $builder) => $builder->where('customers.id', $customer->id)
                        );
                    }

                } else if (!auth()->user()->isAdmin()) {
                    $customers = app('user')->getSelectedCustomers();

                    $builder->whereHas(
                        'customer',
                        fn (Builder $builder) => $builder->whereIn('customers.id', $customers->pluck('id'))
                    );
                }
            }
        }
    }
}
