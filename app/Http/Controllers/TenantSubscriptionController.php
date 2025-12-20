<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Plan;
use App\Services\StripeMarketplaceOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Stancl\Tenancy\Facades\Tenancy;

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
            ->orderBy('price') // optional: if you have amount; otherwise change to ->orderBy('name')
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
        // $tenant = Tenant::findByDomain($request->tenant); // Retrieve tenant from subdomain or path
        $tenant = tenant();

        if (! $tenant) {
            abort(404, 'Tenant not resolved');
        }


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
        // $tenant = Tenant::findByDomain($request->tenant); // Retrieve tenant from subdomain or path
        $tenant = tenant();

        if (! $tenant) {
            abort(404, 'Tenant not resolved');
        }


        return view('tenant.plans.edit_price', compact('plan', 'tenant'));
    }


    /**
     * Update the subscription price for a given plan.
     */
    public function updatePrice(Request $request, Plan $plan)
    {
        // $tenant = Tenant::findByDomain($request->tenant); // Retrieve tenant from subdomain or path
        $tenant = tenant();

        if (! $tenant) {
            abort(404, 'Tenant not resolved');
        }


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


    /**
     * Show the form for creating a new subscription plan.
     */
    public function create(Request $request)
    {
        // $tenant = Tenant::findByDomain($request->tenant);
        $tenant = tenant();

        if (! $tenant) {
            abort(404, 'Tenant not resolved');
        }


        return view('tenant.plans.create', compact('tenant'));
    }

    /**
     * Store a newly created subscription plan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'featured' => 'required|boolean',
            'active' => 'required|boolean',
        ]);        

        // $tenant = Tenant::findByDomain($request->tenant);
        $tenant = tenant();

        if (! $tenant) {
            abort(404, 'Tenant not resolved');
        }


        // Create the plan (Stripe integration handled in StripeMarketplaceOrderService)
        $plan = Plan::create([
            'name' => $validated['name'],
            'stripe_price_id' => null,  // Stripe Price ID will be set later
            'tenant_id' => $tenant->id,
            'featured' => $validated['featured'],
            'price' => $validated['price'],
            'description' => $validated['description'],
            'active' => $validated['active'],
        ]);       

        // After creating the plan, handle Stripe integration
        $stripeService = new StripeMarketplaceOrderService();
        $stripePriceId = $stripeService->createStripePrice($plan);

        // Update the plan with the Stripe price ID
        $plan->update(['stripe_price_id' => $stripePriceId]);

        return redirect()->route('tenant.plans.index', ['tenant' => $tenant->id])
                         ->with('success', 'Subscription plan created successfully.');
    }


    /**
     * Show the form for editing a subscription plan.
     */
    public function edit(Request $request, Plan $plan)
    {
        // $tenant = Tenant::findByDomain($request->tenant);
        $tenant = tenant();

        if (! $tenant) {
            abort(404, 'Tenant not resolved');
        }


        return view('tenant.plans.edit', compact('plan', 'tenant'));
    }

    /**
     * Update the subscription plan.
     */
    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'featured' => 'required|boolean',
            'active' => 'required|boolean',
        ]);

        // $tenant = Tenant::findByDomain($request->tenant);
        $tenant = tenant();

        if (! $tenant) {
            abort(404, 'Tenant not resolved');
        }


        // Update plan fields
        $plan->update([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'description' => $validated['description'],
            'featured' => $validated['featured'],
            'active' => $validated['active'],
        ]);

        // Optionally, you can also handle updating Stripe price if needed

        return redirect()->route('tenant.plans.index', ['tenant' => $tenant->slug])
                         ->with('success', 'Subscription plan updated successfully.');
    }
    
    
}
