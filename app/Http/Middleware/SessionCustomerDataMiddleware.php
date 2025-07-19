<?php

namespace App\Http\Middleware;

use App\Models\CustomerSetting;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class SessionCustomerDataMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $customCSS = null;
        $countries = null;
        $datatableAuditOrder = null;
        $threePLCustomer = null;
        $pageTitle = app('home')->pageTitle();

        if (auth()->user()) {
            $sessionCustomer = app('user')->getSessionCustomer();
            $threePLCustomer = app('user')->get3plCustomer();
            $countries = app('home')->getCountries();
            $datatableAuditOrder = app()->editColumn->getDatatableOrder('audit_log');

            $customCSS = null;

            if ($sessionCustomer) {
                View::share('sessionCustomer', $sessionCustomer);
                $customer = $sessionCustomer;
            } else {
                $customers = app('user')->getSelectedCustomers();

                if ($customers->count()) {
                    $customCSS = customer_settings($customers->first()->id, CustomerSetting::CUSTOMER_SETTING_CUSTOMER_CSS);
                }

                $customer = $customers->first();
            }

            if ($customer) {
                $locale = customer_settings($customer->id, CustomerSetting::CUSTOMER_SETTING_LOCALE);
                if ($locale) {
                    app()->setLocale($locale);
                }

                $customCSS = customer_settings($customer->id, CustomerSetting::CUSTOMER_SETTING_CUSTOMER_CSS);

                if (!$customCSS && $customer->parent_id) {
                    $customCSS = customer_settings($customer->parent_id, CustomerSetting::CUSTOMER_SETTING_CUSTOMER_CSS);
                }
            }
        }

        View::share('customCSS', $customCSS);
        View::share('countries', $countries);
        View::share('datatableAuditOrder', $datatableAuditOrder);
        View::share('threePLCustomer', $threePLCustomer);
        View::share('pageTitle', $pageTitle);

        return $next($request);
    }
}
