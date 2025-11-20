<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\PayPalClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreatorSubscriptionPlanController extends Controller
{
    public function edit(Request $request)
    {
        $tenantId = tenant('id');
        $user     = Auth::user();

        $tenant = Tenant::findOrFail($tenantId);

        $plan = SubscriptionPlan::firstOrNew([
            'tenant_id' => $tenantId,
        ]);

        return view('tenant.creator.plan.edit', compact('plan', 'tenant'));
    }

    public function update(Request $request, PayPalClient $paypal)
    {
        $tenantId = tenant('id');
        $user     = Auth::user();

        $data = $request->validate([
            'monthly_price'       => ['required', 'numeric', 'min:1'],
            'currency'            => ['required', 'string', 'size:3'],
            'paypal_payout_email' => ['nullable', 'email'],
        ]);

        // Update tenant's payout email
        $tenant = Tenant::findOrFail($tenantId);
        $tenant->paypal_payout_email = $data['paypal_payout_email'] ?? $tenant->paypal_payout_email;
        $tenant->save();

        // Create/update local plan
        $plan = SubscriptionPlan::firstOrNew([
            'tenant_id' => $tenantId,
        ]);

        $plan->creator_id     = $user->id;
        $plan->currency       = $data['currency'];
        $plan->monthly_price  = $data['monthly_price'];

        // Call PayPal to create a billing plan with fixed monthly amount
        $paypalPlan = $paypal->createOrUpdatePlan(
            $plan->paypal_plan_id,
            $plan->monthly_price,
            $plan->currency
        );

        $plan->paypal_plan_id = $paypalPlan['id'] ?? $plan->paypal_plan_id;
        $plan->save();

        return back()->with('success', 'Subscription plan updated and synced to PayPal.');
    }
}
