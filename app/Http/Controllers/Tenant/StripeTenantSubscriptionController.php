<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\StripeSubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StripeTenantSubscriptionController extends Controller
{
    /**
     * Show the subscription page for Stripe (status).
     */
    public function index(Request $request)
    {
        $tenantId = tenant('id');
        $user     = Auth::user();

        $plan = SubscriptionPlan::where('tenant_id', $tenantId)->first();

        $subscription = Subscription::where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->where('provider', 'stripe')
            ->latest('id')
            ->first();

        return view('tenant.subscriptions.stripe_status', compact('plan', 'subscription'));
    }

    /**
     * Start Stripe Checkout for subscription.
     */
    public function start(Request $request, StripeSubscriptionService $stripeService)
    {
        $tenantId = tenant('id');
        $user     = Auth::user();

        $plan = SubscriptionPlan::where('tenant_id', $tenantId)->firstOrFail();
        $tenant = Tenant::findOrFail($tenantId);
        
        $successUrl = route('tenant.stripe.subscriptions.success', ['tenant' => $tenantId]);
        $cancelUrl  = route('tenant.stripe.subscriptions.cancel', ['tenant' => $tenantId]);

        try {
            // Ensure plan synced to Stripe
            if (! $plan->stripe_price_id) {                
                $stripeService->syncPlanToStripe($plan, $tenant);
            }

            $session = $stripeService->createCheckoutSession(
                $plan,
                $tenant,
                $successUrl,
                $cancelUrl,
                $user->email
            );
        } catch (\Throwable $e) {
            report($e);
            // return back()->with('error', 'Unable to start Stripe subscription checkout.');
                return back()->with('error', 'Stripe error: '.$e->getMessage());

        }

        // Create local pending subscription
        Subscription::create([
            'provider'              => 'stripe',
            'tenant_id'             => $tenantId,
            'user_id'               => $user->id,
            'subscription_plan_id'  => $plan->id,
            'stripe_subscription_id'=> null,  // set after webhook / success
            'status'                => 'pending',
        ]);

        return redirect()->away($session->url);
    }

    /**
     * Stripe success URL – you can confirm subscription via checkout.session.
     */
    public function success(Request $request, StripeSubscriptionService $stripeService)
    {
        $tenantId   = tenant('id');
        $sessionId  = $request->query('session_id');

        if (! $sessionId) {
            return redirect()->route('tenant.stripe.subscriptions.index', ['tenant' => $tenantId])
                ->with('error', 'Missing Stripe session id.');
        }

        // Use Stripe client directly to retrieve session
        $client  = new \Stripe\StripeClient(config('services.stripe.secret'));
        $session = $client->checkout->sessions->retrieve($sessionId, []);

        $stripeSubscriptionId = $session->subscription ?? null;

        if (! $stripeSubscriptionId) {
            return redirect()->route('tenant.stripe.subscriptions.index', ['tenant' => $tenantId])
                ->with('error', 'No Stripe subscription id on session.');
        }

        $subscription = Subscription::where('tenant_id', $tenantId)
            ->where('user_id', Auth::id())
            ->where('provider', 'stripe')
            ->latest('id')
            ->first();

        if (! $subscription) {
            return redirect()->route('tenant.stripe.subscriptions.index', ['tenant' => $tenantId])
                ->with('error', 'Local subscription not found.');
        }

        // Retrieve subscription details from Stripe
        $stripeSub = $stripeService->getSubscription($stripeSubscriptionId);
        $status = $stripeSub->status; // e.g. 'active', 'trialing', etc.

        $subscription->update([
            'stripe_subscription_id' => $stripeSubscriptionId,
            'status'                 => $status === 'active' ? 'active' : $status,
            'starts_at'              => Carbon::createFromTimestamp($stripeSub->start_date),
            'ends_at'                => Carbon::createFromTimestamp($stripeSub->start_date),
        ]);

        return redirect()->route('tenant.stripe.subscriptions.index', ['tenant' => $tenantId])
            ->with('success', 'Stripe subscription is now active.');
    }

    /**
     * Stripe cancel URL (user backed out of Checkout).
     */
    public function cancel(Request $request)
    {
        $tenantId = tenant('id');

        return redirect()->route('tenant.stripe.subscriptions.index', ['tenant' => $tenantId])
            ->with('error', 'Stripe checkout was cancelled.');
    }
}
