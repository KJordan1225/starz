<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Stripe\StripeClient;

class StripeSubscriptionService
{
    protected StripeClient $client;

    public function __construct()
    {
        $this->client = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Ensure Stripe Product & Price exists for this plan.
     */
    public function syncPlanToStripe(SubscriptionPlan $plan, Tenant $tenant): SubscriptionPlan
    {
        // 1) Product
        if (! $plan->stripe_product_id) {
            $product = $this->client->products->create([
                'name'        => "Creator Subscription for {$tenant->id}",
                'description' => 'Monthly creator subscription',
            ]);

            $plan->stripe_product_id = $product->id;
        }

        // 2) Price (we create a new one for each change in monthly_price)
        $price = $this->client->prices->create([
            'unit_amount' => (int) round($plan->monthly_price * 100),
            'currency'    => strtolower($plan->currency),
            'recurring'   => [
                'interval' => 'month',
            ],
            'product'     => $plan->stripe_product_id,
        ]);

        $plan->stripe_price_id = $price->id;
        $plan->save();

        return $plan;
    }

    /**
     * Create a Stripe Checkout Session for a subscription.
     *
     * Uses Stripe Connect application fees to send 80% to tenant's connected account.
     */
    public function createCheckoutSession(
        SubscriptionPlan $plan,
        Tenant $tenant,
        string $successUrl,
        string $cancelUrl,
        string $customerEmail
    ): \Stripe\Checkout\Session {
        if (! $plan->stripe_price_id) {
            throw new \RuntimeException('Stripe price is not configured for this plan.');
        }

        if (! $tenant->stripe_account_id) {
            throw new \RuntimeException('Tenant has no Stripe Connect account configured.');
        }

        // 20% platform fee
        $applicationFeePercent = 20;

        return $this->client->checkout->sessions->create([
            'mode'        => 'subscription',
            'success_url' => $successUrl.'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $cancelUrl,
            'customer_email' => $customerEmail,
            'line_items'  => [
                [
                    'price'    => $plan->stripe_price_id,
                    'quantity' => 1,
                ],
            ],
            'subscription_data' => [
                'application_fee_percent' => $applicationFeePercent,
                'transfer_data'           => [
                    'destination' => $tenant->stripe_account_id, // creator's connected account
                ],
            ],
        ]);
    }

    /**
     * Cancel a Stripe subscription.
     */
    public function cancelSubscription(string $stripeSubscriptionId, bool $atPeriodEnd = false): void
    {
        $this->client->subscriptions->update($stripeSubscriptionId, [
            'cancel_at_period_end' => $atPeriodEnd,
        ]);
    }

    /**
     * Get Stripe subscription details (for ends_at, status, etc.)
     */
    public function getSubscription(string $stripeSubscriptionId): \Stripe\Subscription
    {
        return $this->client->subscriptions->retrieve($stripeSubscriptionId);
    }

     /**
     * Cancel at period end:
     *   - Stripe will stop billing when current_period_end is reached.
     *   - Subscription stays 'active' until then.
     */
    public function cancelAtPeriodEnd(string $stripeSubscriptionId): \Stripe\Subscription
    {
        return $this->client->subscriptions->update($stripeSubscriptionId, [
            'cancel_at_period_end' => true,
        ]);
    }

    /**
     * Cancel immediately:
     *   - Stripe ends the subscription now.
     *   - You can control proration/invoice_now if you like.
     */
    public function cancelNow(string $stripeSubscriptionId): \Stripe\Subscription
    {
        // If you want to prorate / invoice_now, pass options here.
        return $this->client->subscriptions->cancel($stripeSubscriptionId, [
            'invoice_now' => false,
            'prorate'     => false,
        ]);
    }    

}
