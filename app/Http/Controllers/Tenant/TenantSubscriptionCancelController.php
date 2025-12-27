<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\StripeSubscriptionCancellationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantSubscriptionCancelController extends Controller
{
    public function __construct(
        protected StripeSubscriptionCancellationService $cancellationService
    ) {
        // $this->middleware(['auth']); // tenant auth middleware is on the route group
    }

    /**
     * Show subscription cancellation options page.
     */
    public function show(Tenant $tenant, Request $request): View
    {
        $user = $request->user();

        // You could also check here whether they actually HAVE an active sub
        // and redirect back if not.

        return view('tenant.subscriptions.cancel', [
            'tenant' => $tenant,
            'user'   => $user,
        ]);
    }

    /**
     * Cancel at period end.
     */
    public function cancelAtPeriodEnd(Tenant $tenant, Request $request): RedirectResponse
    {
        $user = $request->user();

        $ok = $this->cancellationService->cancelAtPeriodEnd($user, $tenant);

        if (! $ok) {
            return back()->with('error', 'Unable to schedule cancellation. Please contact support.');
        }

        return back()->with('success', 'Your subscription will remain active until the end of the current period.');
    }

    /**
     * Cancel immediately.
     */
    public function cancelNow(Tenant $tenant, Request $request): RedirectResponse
    {
        $user = $request->user();

        $ok = $this->cancellationService->cancelNow($user, $tenant);

        if (! $ok) {
            return back()->with('error', 'Unable to cancel right now. Please contact support.');
        }

        return back()->with('success', 'Your subscription has been canceled immediately.');
    }
}