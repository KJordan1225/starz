<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\StripeSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StripeCreatorPlanController extends Controller
{
    public function edit(Request $request)
    {
        $tenantId = tenant('id');
        $user     = Auth::user();

        $tenant = Tenant::findOrFail($tenantId);

        $plan = SubscriptionPlan::firstOrNew([
            'tenant_id' => $tenantId,
        ]);

        return view('tenant.creator.stripe_plan.edit', compact('plan', 'tenant'));
    }

    public function update(Request $request, StripeSubscriptionService $stripeService)
    {
        $tenantId = tenant('id');
        $user     = Auth::user();

        $data = $request->validate([
            'monthly_price' => ['required', 'numeric', 'min:1'],
            'currency'      => ['required', 'string', 'size:3'],
        ]);

        $tenant = Tenant::findOrFail($tenantId);

        $plan = SubscriptionPlan::firstOrNew([
            'tenant_id' => $tenantId,
        ]);

        $plan->creator_id    = $user->id;
        $plan->currency      = $data['currency'];
        $plan->monthly_price = $data['monthly_price'];
        $plan->save();

        // Sync to Stripe (product + price)
        $stripeService->syncPlanToStripe($plan, $tenant);

        return back()->with('success', 'Stripe subscription plan updated.');
    }
}
