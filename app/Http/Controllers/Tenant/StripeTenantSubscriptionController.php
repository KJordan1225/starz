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
use Stripe\Exception\ApiErrorException;


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
            'ends_at'                => Carbon::createFromTimestamp($stripeSub->start_date)->addDays(30)
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

     /**
     * Cancel Stripe subscription IMMEDIATELY.
     */
    public function cancelNow(Request $request, StripeSubscriptionService $stripeService, Subscription $subscription)
    {
        $tenantId = tenant('id');
        $user     = Auth::user();

        // Guard: tenant + user + provider=stripe
        if ($subscription->tenant_id !== $tenantId ||
            $subscription->user_id !== $user->id ||
            $subscription->provider !== 'stripe') {
            abort(403);
        }

        if (! $subscription->isActive() || ! $subscription->stripe_subscription_id) {
            return back()->with('error', 'Stripe subscription is not active.');
        }

        try {
            $stripeSub = $stripeService->cancelNow($subscription->stripe_subscription_id);

            $subscription->update([
                'status'              => 'canceled',
                'cancel_at_period_end'=> false,
                'canceled_at'         => Carbon::now(),
                'ends_at'             => Carbon::now(),
            ]);

            return back()->with('success', 'Your Stripe subscription was cancelled immediately.');
        } catch (ApiErrorException $e) {
            report($e);
            $msg = 'Stripe error while cancelling now.';
            if (app()->environment('local')) {
                $msg .= ' '.$e->getMessage();
            }
            return back()->with('error', $msg);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Unable to cancel Stripe subscription immediately.');
        }
    }

    /**
     * Cancel Stripe subscription at end of current billing period.
     *
     * Semantics:
     *  - We tell Stripe cancel_at_period_end = true.
     *  - We store ends_at = current_period_end from Stripe.
     *  - Subscription stays 'active' in our DB until ends_at.
     */
    public function cancelAtPeriodEnd(Request $request, StripeSubscriptionService $stripeService, Subscription $subscription)
    {
        $tenantId = tenant('id');
        $user     = Auth::user();

        if ($subscription->tenant_id !== $tenantId ||
            $subscription->user_id !== $user->id ||
            $subscription->provider !== 'stripe') {
            abort(403);
        }

        if (! $subscription->isActive() || ! $subscription->stripe_subscription_id) {
            return back()->with('error', 'Stripe subscription is not active.');
        }

        try {
            // Ask Stripe to cancel at period end
            $stripeSub = $stripeService->cancelAtPeriodEnd($subscription->stripe_subscription_id);

            // Stripe will include current_period_end timestamp
            $periodEnd = isset($stripeSub->current_period_end)
                ? Carbon::createFromTimestamp($stripeSub->current_period_end)
                : Carbon::now()->addMonth(); // fallback

            $subscription->update([
                'cancel_at_period_end' => true,
                'canceled_at'          => Carbon::now(),
                'ends_at'              => $periodEnd,
                'status'               => 'active', // stays active until ends_at
            ]);

            return back()->with(
                'success',
                'Your Stripe subscription will remain active until '. $periodEnd->format('Y-m-d').'.'
            );
        } catch (ApiErrorException $e) {
            report($e);
            $msg = 'Stripe error while scheduling end-of-period cancellation.';
            if (app()->environment('local')) {
                $msg .= ' '.$e->getMessage();
            }
            return back()->with('error', $msg);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Unable to cancel Stripe subscription at period end.');
        }
    }

}
