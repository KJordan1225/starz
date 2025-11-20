<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\PayPalClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantSubscriptionController extends Controller
{
    /**
     * Show the current user’s subscription to this tenant’s microsite.
     */
    public function index(Request $request)
    {
        $tenantId = tenant('id');
        $user     = Auth::user();

        $plan = SubscriptionPlan::where('tenant_id', $tenantId)->first();
        $subscription = Subscription::where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        return view('tenant.subscriptions.index', compact('plan', 'subscription'));
    }

    /**
     * Start a PayPal subscription (standard API).
     */
    public function start(Request $request, PayPalClient $paypal)
    {
        $tenantId = tenant('id');
        $user     = Auth::user();

        $plan = SubscriptionPlan::where('tenant_id', $tenantId)->firstOrFail();

        $returnUrl = route('tenant.subscriptions.approve', ['tenant' => $tenantId]);
        $cancelUrl = route('tenant.subscriptions.cancel', ['tenant' => $tenantId]);

        $data = $paypal->createSubscription($plan->paypal_plan_id, $returnUrl, $cancelUrl);

        $paypalSubId = $data['id'] ?? null;
        $approveLink = collect($data['links'] ?? [])
            ->firstWhere('rel', 'approve')['href'] ?? null;

        if (! $paypalSubId || ! $approveLink) {
            return back()->with('error', 'Unable to start PayPal subscription.');
        }

        Subscription::create([
            'tenant_id'              => $tenantId,
            'user_id'                => $user->id,
            'subscription_plan_id'   => $plan->id,
            'paypal_subscription_id' => $paypalSubId,
            'status'                 => 'pending',
        ]);

        return redirect()->away($approveLink);
    }

    /**
     * PayPal return URL on success.
     */
    public function approve(Request $request, PayPalClient $paypal)
    {
        $tenantId = tenant('id');

        $paypalSubscriptionId = $request->query('subscription_id');

        if (! $paypalSubscriptionId) {
            return redirect()->route('tenant.subscriptions.index', ['tenant' => $tenantId])
                ->with('error', 'Missing subscription id.');
        }

        $details = $paypal->getSubscription($paypalSubscriptionId);
        $status  = strtolower($details['status'] ?? 'unknown');

        $subscription = Subscription::where('tenant_id', $tenantId)
            ->where('paypal_subscription_id', $paypalSubscriptionId)
            ->latest('id')
            ->first();

        if (! $subscription) {
            return redirect()->route('tenant.subscriptions.index', ['tenant' => $tenantId])
                ->with('error', 'Subscription not found.');
        }

        if ($status === 'active') {
            $subscription->update([
                'status'    => 'active',
                'starts_at' => Carbon::now(),
            ]);

            return redirect()->route('tenant.subscriptions.index', ['tenant' => $tenantId])
                ->with('success', 'Subscription is now active.');
        }

        return redirect()->route('tenant.subscriptions.index', ['tenant' => $tenantId])
            ->with('error', 'Subscription not active. Status: '.$details['status'] ?? 'unknown');
    }

    /**
     * PayPal cancel URL (user backed out of approval screen).
     */
    public function cancelView(Request $request)
    {
        $tenantId = tenant('id');

        return redirect()->route('tenant.subscriptions.index', ['tenant' => $tenantId])
            ->with('error', 'PayPal checkout was cancelled.');
    }

    /**
     * CANCEL IMMEDIATELY:
     * - Calls standard PayPal cancel endpoint.
     * - Ends access now by setting ends_at = now().
     */
    public function cancelNow(Request $request, PayPalClient $paypal, Subscription $subscription)
    {
        $tenantId = tenant('id');
        $user     = Auth::user();

        // Guard: ensure this subscription belongs to this tenant + user
        if ($subscription->tenant_id !== $tenantId || $subscription->user_id !== $user->id) {
            abort(403);
        }

        if (! $subscription->isActive()) {
            return back()->with('error', 'Subscription is not active.');
        }

        try {
            $paypal->cancelSubscription(
                $subscription->paypal_subscription_id,
                'IMMEDIATE',
                'User requested immediate cancellation'
            );

            $subscription->update([
                'status'              => 'canceled',
                'cancel_at_period_end'=> false,
                'canceled_at'         => Carbon::now(),
                'ends_at'             => Carbon::now(), // access stops now
            ]);

            return back()->with('success', 'Subscription cancelled immediately.');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Unable to cancel subscription at PayPal.');
        }
    }

    /**
     * CANCEL AT PERIOD END (your semantics, still using standard API):
     * - Read billing_info.next_billing_time from PayPal.
     * - Call standard cancel endpoint.
     * - Keep status 'active' in your DB, but set ends_at to that time.
     *   Your Subscription::isActive() will flip to false when ends_at is past.
     */
    public function cancelAtPeriodEnd(Request $request, PayPalClient $paypal, Subscription $subscription)
    {
        $tenantId = tenant('id');
        $user     = Auth::user();

        if ($subscription->tenant_id !== $tenantId || $subscription->user_id !== $user->id) {
            abort(403);
        }

        if (! $subscription->isActive()) {
            return back()->with('error', 'Subscription is not active.');
        }

        $paypalSubId = $subscription->paypal_subscription_id;

        try {
            // 1) Ask PayPal for next billing time
            $details = $paypal->getSubscription($paypalSubId);
            $nextBillingTime = $details['billing_info']['next_billing_time'] ?? null;

            // If missing, default to 1 month from now
            $endsAt = $nextBillingTime
                ? Carbon::parse($nextBillingTime)
                : Carbon::now()->addMonth();

            // 2) Call standard cancel endpoint (no usage-based API)
            $paypal->cancelSubscription(
                $paypalSubId,
                'END_OF_PERIOD',
                'User requested cancel at period end'
            );

            // 3) In YOUR app, mark it as "active, but pending end at ends_at"
            $subscription->update([
                'cancel_at_period_end' => true,
                'canceled_at'          => Carbon::now(),
                'ends_at'              => $endsAt,
                'status'               => 'active', // still active until ends_at
            ]);

            return back()->with('success', 'Subscription will remain active until the end of the current billing period.');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Unable to schedule end-of-term cancellation.');
        }
    }
}
