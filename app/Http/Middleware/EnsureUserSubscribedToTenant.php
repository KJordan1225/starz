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
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        /** @var Tenant|null $tenant */
        $tenant = $request->route('tenant');

        if (! $tenant) {
            abort(404, 'Tenant not found.');
        }

        if (! $user->hasActiveSubscriptionForTenant($tenant)) {
            // Customize: redirect to upsell / plans page
            return redirect()
                ->route('tenant.plans.index', ['tenant' => $tenant->id])
                ->with('error', 'You must subscribe to access this content.');
        }

        return $next($request);
    }
}