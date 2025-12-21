<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserSubscribedToTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Get 'tenant' from the route
        $routeTenant = $request->route('tenant');

        // Normalize to a Tenant model
        if ($routeTenant instanceof Tenant) {
            $tenant = $routeTenant;
        } else {
            // If you're using ID as param:
            $tenant = Tenant::where('id', $routeTenant)
                // or if you sometimes use slug:
                // ->orWhere('slug', $routeTenant)
                ->firstOrFail();
        }

        if ($user->hasRole('super-admin')) {
			return $next($request);
		}

        if (! $user->hasActiveSubscriptionForTenant($tenant)) {
            return redirect()
                ->route('tenant.plans.index', ['tenant' => $tenant->id])
                ->with('error', 'You must subscribe to access this content.');
        }        

        return $next($request);
    }
}