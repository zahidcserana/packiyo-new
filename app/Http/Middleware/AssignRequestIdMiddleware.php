<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AssignRequestIdMiddleware
{
    const REQUEST_ID_ENV = 'UNIQUE_ID';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $requestId = getenv(self::REQUEST_ID_ENV);

        if (!$requestId) {
            $requestId = Str::uuid()->toString();
        }

        Log::withContext([
            'request_id' => $requestId,
        ]);

        return $next($request);
    }
}
