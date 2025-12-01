<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;

class InitializeTenantFromPath
{
    public function handle(Request $request, Closure $next)
    {
        // Get Tenancy manager via helper
        $tenancy = tenancy(); // instance of Stancl\Tenancy\Tenancy

        // Already initialized → nothing to do
        if ($tenancy->initialized) {
            return $next($request);
        }

        // Prefer route param `{tenant}`, fall back to first segment
        $segment = $request->route('tenant') ?? $request->segment(1);

        if ($segment) {
            $tenant = Tenant::query()
                ->where('id', $segment)
                // ->orWhere('slug', $segment) // enable this if you use slugs
                ->first();

            if ($tenant) {
                $tenancy->initialize($tenant);
            }
            // If no tenant found: do nothing (landlord/public routes still work)
        }

        return $next($request);
    }
}
