<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserSubscribedToTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1) Require auth
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        // 2) Resolve tenant key from route OR URL segment (/{tenant}/...)
        $tenantKey = $request->route('tenant') ?? $request->segment(1);

        if (! $tenantKey) {
            // No tenant context: choose your desired behavior
            abort(404, 'Tenant context missing.');
        }

        // 3) Normalize to Tenant model (supports model binding, id, or slug)
        $tenant = $tenantKey instanceof Tenant
            ? $tenantKey
            : Tenant::query()
                ->where('id', (string) $tenantKey)
                ->when(
                    ! Str::isUuid((string) $tenantKey)                    
                )
                ->firstOrFail();

        // 4) Ensure the *current tenant* is initialized (important in single-db tenancy too)
        /** @var Tenancy $tenancy */
        $tenancy = app(Tenancy::class);

        if (! $tenancy->initialized || optional(tenant())->getTenantKey() !== $tenant->getTenantKey()) {
            $tenancy->initialize($tenant);
        }

        // 5) Gate: user must have an active subscription to *this* tenant
        if (! $user->hasActiveSubscriptionForTenant($tenant)) {
            // Optional: end tenancy context before redirect (keeps redirects clean)
            $tenancy->end();

            return redirect()
                ->route('tenant.plans.index', ['tenant' => $tenant->getTenantKey()])
                ->with('error', 'You must subscribe to access this content.');
        }

        return $next($request);
    }
}
