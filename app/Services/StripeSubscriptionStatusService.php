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
        // dd('isActiveForUserAndTenant-true');
        // 2) Latest subscription order for this user + tenant
        
        $order = Order::query()
            ->where('order_type', 'subscription')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->whereNotNull('stripe_subscription_id')
            ->first();

        if (! $order) {
            return false;
        }          
        
        

        // try {
            
        //     // 3) Retrieve subscription from the TENANT'S connected Stripe account
        //     $subscription = $this->stripe->subscriptions->retrieve(
        //         $order->stripe_subscription_id,
        //         [], // params
        //         ['stripe_account' => $tenant->stripe_account_id] // <-- CRITICAL            
        //     );
        // } catch (ApiErrorException $e) {
        //     // Optional: fallback to your local order status if you keep it in sync via webhooks
        //     // return in_array($order->status, ['active'], true);

        //     dd('Stripe API error: ' . $e->getMessage());
        //     return false;
        // }

        try {
            $subscription = $this->stripe->subscriptions->retrieve(
                $order->stripe_subscription_id // no 3rd options param
            );
        } catch (\Stripe\Exception\ApiErrorException $e) {
            \Log::error('Stripe subscription retrieve failed (platform)', [
                'subscription_id' => $order->stripe_subscription_id,
                'error'           => $e->getMessage(),
            ]);

            dd('Stripe API error: ' . $e->getMessage());
            return false;
        }


        // return true;
        return in_array($subscription->status, ['active', 'trialing'], true);
    }


    /**
     * Check whether the subscription is on a connected account or platform account.
     *
     * 1. Try retrieving the subscription using the connected account (if it exists).
     * 2. If that fails, fallback to the platform account.
     */
    public function isSubscriptionOnConnectedOrPlatformAccount(User $user, Tenant $tenant): string
    {
        $order = Order::query()
            ->where('order_type', 'subscription')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->whereNotNull('stripe_subscription_id')
            // ->latest('created_at')
            ->first();

        if (! $order) {
            return 'no_order_found'; // No subscription order
        }

        $subscriptionId = $order->stripe_subscription_id;
        $subscription = null;

        // 1. Try retrieving the subscription from the connected account
        if (! empty($tenant->stripe_account_id)) {
            try {
                $subscription = $this->stripe->subscriptions->retrieve(
                    $subscriptionId,
                    [],
                    ['stripe_account' => $tenant->stripe_account_id]  // Use connected account
                );
                // If this succeeds, it means it's a connected account subscription
                return 'connected_account';
            } catch (ApiErrorException $e) {
                \Log::warning('Stripe subscription retrieve failed (connected account)', [
                    'tenant_id'       => $tenant->id,
                    'stripe_account'  => $tenant->stripe_account_id,
                    'subscription_id' => $subscriptionId,
                    'error'           => $e->getMessage(),
                ]);
            }
        }

        // 2. If that fails, try retrieving the subscription from the platform account
        try {
            $subscription = $this->stripe->subscriptions->retrieve($subscriptionId);
            // If this succeeds, it means it's a platform account subscription
            return 'platform_account';
        } catch (ApiErrorException $e) {
            \Log::error('Stripe subscription retrieve failed (platform account)', [
                'tenant_id'       => $tenant->id,
                'subscription_id' => $subscriptionId,
                'error'           => $e->getMessage(),
            ]);
            return 'subscription_not_found';  // Neither found
        }
    }

}