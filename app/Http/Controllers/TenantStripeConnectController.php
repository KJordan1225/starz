<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\StripeConnectOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantStripeConnectController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        return view('tenant.admin.stripe.connect', [
            'tenant' => $tenant,
        ]);
    }

    public function start(Request $request, Tenant $tenant): RedirectResponse
    {
        // Optionally protect this with your tenant-admin middleware/authorization
        // abort_unless($request->user()?->can('manageTenant', $tenant), 403);

        $service = StripeConnectOnboardingService::make();

        $refreshUrl = route('tenant.stripe.connect.refresh', ['tenant' => $tenant->id], true);
        $returnUrl  = route('tenant.stripe.connect.return',  ['tenant' => $tenant->id], true);

        $url = $service->createOnboardingLink($tenant, $refreshUrl, $returnUrl);

        return redirect()->away($url);
    }

    public function return(Request $request, Tenant $tenant): RedirectResponse
    {
        $service = StripeConnectOnboardingService::make();
        $service->syncOnboardingStatus($tenant);

        return redirect()
            ->route('tenant.stripe.connect.index', ['tenant' => $tenant->id])
            ->with('success', $tenant->stripe_onboarded_at ? 'Stripe onboarding completed.' : 'Stripe onboarding started. Finish steps in Stripe to enable payouts.');
    }

    public function refresh(Request $request, Tenant $tenant): RedirectResponse
    {
        // Just send them back to the Connect page to click “Continue”
        return redirect()
            ->route('tenant.stripe.connect.index', ['tenant' => $tenant->id])
            ->with('error', 'Onboarding was not completed. Please continue.');
    }
}