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

    public function save(
        Request $request,
        StripeSubscriptionService $stripeSubscriptionService
    ) {
        $tenantId = tenant('id');
        $tenant   = Tenant::findOrFail($tenantId);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount'      => ['required', 'numeric', 'min:1'], // dollars
            'interval'    => ['required', 'in:month,year'],
            'active'      => ['nullable'], // checkbox
        ]);

        // Convert dollars → cents
        $amountInCents = (int) round($data['amount'] * 100);

        $plan = SubscriptionPlan::firstOrNew([
            'tenant_id' => $tenantId,
        ]);

        $plan->name        = $data['name'];
        $plan->description = $data['description'] ?? null;
        $plan->amount      = $amountInCents;
        $plan->currency    = $plan->currency ?? 'usd';
        $plan->interval    = $data['interval'];
        $plan->active      = isset($data['active']) ? true : false;

        $plan->save();

        // Sync to Stripe (Product + Price; immutable price handling)
        $stripeSubscriptionService->syncPlanToStripe($plan, $tenant);

        return redirect()
            ->route('edit.subscription-plan', ['tenant' => $tenantId])
            ->with('success', 'Subscription plan saved and synced to Stripe.');
    }
}
