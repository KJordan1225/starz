<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Stripe\StripeClient;

class StripeMarketplaceOrderService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create a Checkout Session for a marketplace SUBSCRIPTION (destination charge).
     *
     * @param  Tenant $tenant   Creator/seller tenant (destination account)
     * @param  User   $buyer    Subscriber
     * @param  string $priceId  Stripe recurring Price ID (price_...)
     * @param  array  $options  Overrides: description, metadata, success_url, cancel_url
     *
     * @return array [StripeOrder $order, \Stripe\Checkout\Session $session]
     */
    public function createSubscriptionCheckoutSession(
        Tenant $tenant,
        User $buyer,
        string $priceId,
        array $options = []
    ): array {
        $creatorStripeAccountId = $tenant->stripe_account_id;

        if (! $creatorStripeAccountId) {
            throw new \RuntimeException('Tenant has no Stripe Connect account configured.');
        }

        $successUrl = $options['success_url'] ?? $this->buildSuccessUrl($tenant);
        $cancelUrl  = $options['cancel_url']  ?? $this->buildCancelUrl($tenant);

        $metadata = $this->buildMetadata($tenant, $buyer, $options['metadata'] ?? []);
        $description = $options['description'] ?? "Subscription for tenant {$tenant->id}";

        // Subscriptions use % fee (recommended) rather than application_fee_amount
        $applicationFeePercent = $this->getPlatformFeePercent();

        // 1) Create Stripe Checkout Session (subscription mode)
        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'payment_method_types' => ['card'],

            'client_reference_id' => (string) $buyer->id,
            'customer_email'      => $buyer->email,

            'line_items' => [[
                'price'    => $priceId,
                'quantity' => 1,
            ]],

            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,

            'subscription_data' => [
                'application_fee_percent' => $applicationFeePercent,
                'transfer_data' => [
                    'destination' => $creatorStripeAccountId,
                ],
                'metadata'    => $metadata,
                'description' => $description,
            ],

            'metadata' => $metadata,
        ]);

        // 2) Persist order locally
        // NOTE: session->subscription may be null until checkout completes;
        // webhook will populate stripe_subscription_id.
        $order = Order::create([
            'order_type'             => 'subscription',
            'tenant_id'              => $tenant->id,
            'user_id'                => $buyer->id,
            'stripe_session_id'      => $session->id,
            'stripe_subscription_id' => $session->subscription ?? null,
            'status'                 => 'created',
            'metadata'               => $metadata,
            'raw_payload'            => $session->toArray(),
        ]);

        return [$order, $session];
    }

    public function getPlatformFeePercent(): float
    {
        return (float) config('stripe_marketplace.platform_fee_percent', 20);
    }

    protected function buildMetadata(Tenant $tenant, User $buyer, array $extra = []): array
    {
        return array_merge($extra, [
            'tenant_id'   => $tenant->id,
            'buyer_id'    => $buyer->id,
            'order_uuid'  => (string) Str::uuid(),
            'order_type'  => 'subscription',
        ]);
    }

    protected function buildSuccessUrl(Tenant $tenant): string
    {
        if (function_exists('route')) {
            // Prefer: .../subscribe/success?session_id={CHECKOUT_SESSION_ID}
            return route('tenant.subscribe.success', ['tenant' => $tenant->id], true)
                . '?session_id={CHECKOUT_SESSION_ID}';
        }

        return rtrim((string) config('stripe_marketplace.success_url'), '/');
    }

    protected function buildCancelUrl(Tenant $tenant): string
    {
        if (function_exists('route')) {
            return route('tenant.subscribe.cancel', ['tenant' => $tenant->id], true);
        }

        return rtrim((string) config('stripe_marketplace.cancel_url'), '/');
    }
}