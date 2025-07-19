<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DataTableMaxLength
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (str_contains($request->url(), 'data-table') && $request->has('length')) {
            $maxLength = max(config('datatable.length_menu'));

            if ($request->input('length') > $maxLength) {
                $request->merge(['length' => $maxLength]);
            }
        }

        return $next($request);
    }
}
