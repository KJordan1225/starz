<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripeSubscriptionCancellationService
{
    public function __construct(
        protected StripeClient $stripe
    ) {
        //
    }

    /**
     * Cancel subscription at period end.
     */
    public function cancelAtPeriodEnd(User $user, Tenant $tenant): bool
    {
        $order = $this->getLatestSubscriptionOrder($user, $tenant);

        if (! $order || ! $order->stripe_subscription_id) {
            return false;
        }

        $cancelled = false;

        // 1) Attempt to mark subscription to cancel at period end on the CONNECTED account
        if (! empty($tenant->stripe_account_id)) {
            try {
                // Attempt to set 'cancel_at_period_end' on the connected account
                $this->stripe->subscriptions->update(
                    $order->stripe_subscription_id,
                    [
                        'cancel_at_period_end' => true,
                    ],
                    [
                        'stripe_account' => $tenant->stripe_account_id,  // Target connected account
                    ]
                );
                $cancelled = true;  // Mark as successful if no exception is thrown
            } catch (ApiErrorException $e) {
                // Log the error for troubleshooting
                \Log::warning('Stripe cancelAtPeriodEnd failed on connected account', [
                    'tenant_id'       => $tenant->id,
                    'stripe_account'  => $tenant->stripe_account_id,
                    'subscription_id' => $order->stripe_subscription_id,
                    'error'           => $e->getMessage(),
                ]);
            }
        }

        // 2) If cancellation on the connected account failed, attempt to mark for cancellation on the platform account
        if (! $cancelled) {
            try {
                // Attempt to set 'cancel_at_period_end' on the platform account (default account)
                $this->stripe->subscriptions->update(
                    $order->stripe_subscription_id,
                    [
                        'cancel_at_period_end' => true,
                    ]
                );
                $cancelled = true;  // Mark as successful if no exception is thrown
            } catch (ApiErrorException $e) {
                // Log the error for troubleshooting
                \Log::error('Stripe cancelAtPeriodEnd failed on platform account', [
                    'tenant_id'       => $tenant->id,
                    'subscription_id' => $order->stripe_subscription_id,
                    'error'           => $e->getMessage(),
                ]);
                return false;
            }
        }

        // 3) Optionally reflect locally (if you have a column like `cancel_at_period_end`)
        if (isset($order->cancel_at_period_end)) {
            $order->cancel_at_period_end = true;
        }

        // We usually keep the status as 'active' until Stripe sends the customer.subscription.updated webhook
        $order->save();

        return true;
    }

    /**
     * Cancel subscription immediately.
     */
    public function cancelNow(User $user, Tenant $tenant): bool
    {
        $order = $this->getLatestSubscriptionOrder($user, $tenant);

        $this->stripe = new StripeClient(config('services.stripe.secret'));        

        if (! $order || ! $order->stripe_subscription_id) {
            return false;
        }


        // try {
        //     // 1) Cancel immediately on CONNECTED account
        //     $this->stripe->subscriptions->cancel(
        //         $order->stripe_subscription_id,
        //         [
        //             'invoice_now' => true,  // optional
        //             'prorate'     => true,  // optional, tweak to your policy
        //         ],
        //         [
        //             'stripe_account' => $tenant->stripe_account_id,
        //         ]
        //     );
            
        // } catch (ApiErrorException $e) {

        //     // dd('Stripe API error: ' . $e->getMessage());

        //     \Log::warning('Stripe cancelNow failed', [
        //         'tenant_id'   => $tenant->id,
        //         'user_id'     => $user->id,
        //         'order_id'    => $order->id,
        //         'stripe_sub'  => $order->stripe_subscription_id,
        //         'message'     => $e->getMessage(),
        //     ]);

        //     return false;
        // }

        $cancelled = false;
        // Check if the tenant has a connected account and attempt to cancel there
        if (! empty($tenant->stripe_account_id)) {
            try {
                // Attempt to cancel on the connected account
                $this->stripe->subscriptions->cancel(
                    $order->stripe_subscription_id,
                    [
                        'invoice_now' => true,  // Optional: Invoices now
                        'prorate'     => true,  // Optional: Apply prorate if needed
                    ],
                    [
                        'stripe_account' => $tenant->stripe_account_id, // Target connected account
                    ]
                );
                $cancelled = true;
            } catch (\Stripe\Exception\ApiErrorException $e) {
                // Log the error for troubleshooting
                \Log::warning('Stripe cancel failed on connected account', [
                    'tenant_id'       => $tenant->id,
                    'stripe_account'  => $tenant->stripe_account_id,
                    'subscription_id' => $order->stripe_subscription_id,
                    'error'           => $e->getMessage(),
                ]);
            }
        }


        // If cancellation on the connected account failed, attempt to cancel on the platform account
        if (! $cancelled) {
            try {
                // Attempt to cancel on the platform account (default Stripe account)
                $this->stripe->subscriptions->cancel(
                    $order->stripe_subscription_id,
                    [
                        'invoice_now' => true,  // Optional: Invoices now
                        'prorate'     => true,  // Optional: Apply prorate if needed
                    ]
                );
                $cancelled = true;
            } catch (\Stripe\Exception\ApiErrorException $e) {
                // Log the error for troubleshooting
                \Log::error('Stripe cancel failed on platform account', [
                    'tenant_id'       => $tenant->id,
                    'subscription_id' => $order->stripe_subscription_id,
                    'error'           => $e->getMessage(),
                ]);
                return false;
            }
        }



        // 2) Immediately set local status to 'canceled'
        $order->status = 'canceled';   // must exist in your ENUM
        if (isset($order->cancel_at_period_end)) {
            $order->cancel_at_period_end = false;
        }
        $order->save();

        return true;
    }

    /**
     * Get latest subscription order for this user + tenant.
     */
    protected function getLatestSubscriptionOrder(User $user, Tenant $tenant): ?Order
    {
        return Order::query()
            ->where('order_type', 'subscription')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->whereNotNull('stripe_subscription_id')
            ->latest('created_at')
            ->first();
    }
}