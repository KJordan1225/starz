<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TenantDefaults
{
    public function handle(Request $request, Closure $next)
    {
        if (function_exists('tenant') && tenant()) {
            // Example: set app.url for links, share tenant to views, etc.
            config(['app.name' => tenant('data.name') ?? config('app.name')]);
            view()->share('tenantId', (string) tenant('id'));
        }
        return $next($request);
    }
}