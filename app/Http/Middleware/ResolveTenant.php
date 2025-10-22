<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Customer;

class ResolveTenant
{
    public function handle($request, Closure $next)
    {
        $host = $request->getHost();
        $tenant = null;

        // 1. Try resolve by custom store_domain
        $tenant = Customer::where('store_domain', $host)->first();

        // 2. Try resolve by subdomain (e.g. acme.yourdomain.com)
        if (!$tenant && config('app.main_domain')) {
            $mainDomain = config('app.main_domain'); // e.g. yourdomain.com

            if (str_ends_with($host, $mainDomain)) {
                $subdomain = str_replace('.' . $mainDomain, '', $host);

                if ($subdomain && $subdomain !== 'www') {
                    $tenant = Customer::where('slug', $subdomain)->first();
                }
            }
        }

        // 3. Try resolve by slug in URL path
        if (!$tenant && $request->route('tenantSlug')) {
            $tenant = Customer::where('slug', $request->route('tenantSlug'))->first();
        }

        if (!$tenant) {
            return response()->json(['error' => 'Invalid tenant'], 404);
        }

        // Bind tenant globally
        app()->instance('tenant', $tenant);

        // Inject tenant_id into request
        $request->merge(['tenant_id' => $tenant->id]);

        return $next($request);
    }
}

