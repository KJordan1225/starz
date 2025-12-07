<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\StripeConnectService;

class StripeConnectController extends Controller
{
    public function __construct(
        protected StripeConnectService $connectService
    ) {}

    public function start(Tenant $tenant)
    {
        $url = $this->connectService->createOnboardingLink($tenant);

        return redirect()->away($url);
    }

    public function refresh(Tenant $tenant)
    {
        $url = $this->connectService->createOnboardingLink($tenant);

        return redirect()->away($url);
    }

    public function return(Tenant $tenant)
    {
        // Optionally re-fetch account to see if details_submitted is true.
        // For now, just mark them as "pending verification" etc.
        return redirect()->route('tenant.admin.home', ['tenant' => $tenant->id])
            ->with('success', 'Stripe onboarding completed or in progress.');
    }
}