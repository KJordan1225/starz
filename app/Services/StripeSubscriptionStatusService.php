<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeSubscriptionStatusService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Check if a user has an active subscription for a tenant.
     *
     * 1) Find the latest subscription-type order for this user + tenant.
     * 2) Use stripe_subscription_id from that order.
     * 3) Ask Stripe for the subscription status.
     */
    // use App\Models\Order;
    // use App\Models\Tenant;
    // use App\Models\User;
    // use Stripe\Exception\ApiErrorException;

    public function isActiveForUserAndTenant(User $user, Tenant $tenant): bool
    {
        // 1) Must have a connected account (if your subscriptions live on tenant accounts)
        if (empty($tenant->stripe_account_id)) {
            return false;
        }

        // 2) Latest subscription order for this user + tenant
        $order = Order::query()
            ->where('order_type', 'subscription')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->whereNotNull('stripe_subscription_id')
            ->latest('created_at')
            ->first();

        if (! $order) {
            return false;
        }

        try {
            // 3) Retrieve subscription from the TENANT'S connected Stripe account
            $subscription = $this->stripe->subscriptions->retrieve(
                $order->stripe_subscription_id,
                [], // params
                ['stripe_account' => $tenant->stripe_account_id] // <-- CRITICAL
            );
        } catch (ApiErrorException $e) {
            // Optional: fallback to your local order status if you keep it in sync via webhooks
            // return in_array($order->status, ['active'], true);

            return false;
        }

        return in_array($subscription->status, ['active', 'trialing'], true);
    }

}