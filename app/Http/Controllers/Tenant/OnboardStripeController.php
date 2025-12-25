<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Stripe\StripeClient;

class OnboardStripeController extends Controller
{
    public function start(Request $request)
    {
        
        $tenantId = tenant('id');
        $tenant   = Tenant::findOrFail($tenantId);

        $stripe = new StripeClient(config('services.stripe.secret'));

        // 1) If tenant already has stripe_account_id, reuse it
        if ($tenant->stripe_account_id) {
            $accountId = $tenant->stripe_account_id;
        } else {
            // 2) Create a new Express account
            $account = $stripe->accounts->create([
                'type' => 'express',
                'business_type' => 'individual',
            ]);

            $accountId = $account->id;

            // Save to tenant
            $tenant->stripe_account_id = $accountId;
            $tenant->save();
        }

        // 3) Create onboarding link
        $link = $stripe->accountLinks->create([
            'account'     => $accountId,
            'refresh_url' => route('tenant.stripe.onboard.start', ['tenant' => $tenantId]),
            'return_url'  => route('tenant.stripe.onboard.complete', ['tenant' => $tenantId]),
            'type'        => 'account_onboarding',
        ]);

        return redirect()->away($link->url);
    }

    public function complete(Request $request)
    {
        // Just send them back somewhere nice, e.g. plan page
        return redirect()
            ->route('tenant.stripe.plan.edit', ['tenant' => tenant('id')])
            ->with('success', 'Stripe Connect onboarding completed (or in progress).');
    }
}
