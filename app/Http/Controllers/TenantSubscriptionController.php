<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Plan;
use App\Services\StripeMarketplaceOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantSubscriptionController extends Controller
{
    /**
     * Show the tenant's available subscription plans.
     */
    public function index(Request $request, Tenant $tenant): View
    {
        $plans = Plan::query()
            ->where('tenant_id', $tenant->id)
            ->where('active', true)
            ->orderBy('amount') // optional: if you have amount; otherwise change to ->orderBy('name')
            ->get();

        return view('tenant.plans.index', [
            'tenant' => $tenant,
            'plans'  => $plans,
        ]);
    }

    /**
     * Start a Stripe Checkout subscription for a plan (tenant-aware).
     */
    public function start(
        Request $request,
        Tenant $tenant,
        Plan $plan,
        StripeMarketplaceOrderService $orders
    ): RedirectResponse {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Safety: do not allow subscriptions unless tenant onboarded
        abort_unless($tenant->stripe_account_id, 400, 'Creator is not connected to Stripe yet.');
        // Safety: ensure plan belongs to this tenant
        abort_unless((string) $plan->tenant_id === (string) $tenant->id, 404);

        if (! $plan->stripe_price_id) {
            abort(400, 'Plan is missing stripe_price_id.');
        }

        [$order, $session] = $orders->createSubscriptionCheckoutSession(
            tenant:  $tenant,
            buyer:   $user,
            priceId: $plan->stripe_price_id,
            options: [
                'description' => "Subscription to {$tenant->name} ({$plan->name})",
                'metadata'    => [
                    'plan_id' => $plan->id,
                ],
            ]
        );

        return redirect()->away($session->url);
    }


    public function subscribe(Request $request, Plan $plan)
    {
        $tenant = Tenant::findByDomain($request->tenant); // Retrieve tenant from subdomain or path

        // Get user if logged in, or null for anonymous users
        $user = auth()->user();

        // Create Stripe Checkout session for subscription
        $stripeService = new StripeMarketplaceOrderService();
        $checkoutUrl = $stripeService->createCheckoutSession($plan, $tenant, $user);

        return redirect()->to($checkoutUrl);
    }


    /**
     * Show the form for editing the subscription price of a plan.
     */
    public function editPrice(Request $request, Plan $plan)
    {
        $tenant = Tenant::findByDomain($request->tenant); // Retrieve tenant from subdomain or path

        return view('tenant.plans.edit_price', compact('plan', 'tenant'));
    }


    /**
     * Update the subscription price for a given plan.
     */
    public function updatePrice(Request $request, Plan $plan)
    {
        $tenant = Tenant::findByDomain($request->tenant); // Retrieve tenant from subdomain or path

        // Validate price input
        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
        ]);

        // Update the price for the plan
        $plan->update([
            'price' => $validated['price'],
        ]);

        return redirect()->route('tenant.plans.index', ['tenant' => $tenant->slug])
                         ->with('success', 'Subscription price updated successfully.');
    }

    
    
}
