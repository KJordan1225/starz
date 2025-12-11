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
    public function isActiveForUserAndTenant(User $user, Tenant $tenant): bool
    {
        // Latest subscription order for this user + tenant
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
            $subscription = $this->stripe->subscriptions->retrieve(
                $order->stripe_subscription_id,
                []
            );
        } catch (ApiErrorException $e) {
            // Log if you want: \Log::warning('Stripe subscription check failed', [...])
            return false;
        }

        // Choose which statuses you consider "active"
        return in_array($subscription->status, ['active', 'trialing'], true);
    }
}