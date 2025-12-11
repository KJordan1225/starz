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

    /*
    |--------------------------------------------------------------------------
    | ONE-TIME PAYMENTS (tips, PPV, etc.)
    |--------------------------------------------------------------------------
    */

    public function createOneTimeOrderCheckoutSession(
        Tenant $tenant,
        User $buyer,
        int|float $amount,
        ?string $currency = null,
        array $options = []
    ): array {
        $currency      = $currency ?: config('stripe_marketplace.currency', 'usd');
        $amountInCents = (int) round($amount * 100);
        $platformFee   = $this->calculatePlatformFeeAmount($amountInCents);
        $destination   = $tenant->stripe_account_id;

        if (! $destination) {
            throw new \RuntimeException('Tenant has no Stripe Connect account configured.');
        }

        $successUrl = $options['success_url'] ?? $this->buildSuccessUrl($tenant);
        $cancelUrl  = $options['cancel_url']  ?? $this->buildCancelUrl($tenant);

        $metadata = $this->buildMetadata($tenant, $buyer, $options['metadata'] ?? []);
        $description = $options['description'] ?? "Order for tenant {$tenant->id}";

        $session = $this->stripe->checkout->sessions->create([
            'mode'                 => 'payment',
            'payment_method_types' => ['card'],
            'client_reference_id'  => (string) $buyer->id,
            'customer_email'       => $buyer->email,

            'line_items' => [[
                'price_data' => [
                    'currency'     => $currency,
                    'unit_amount'  => $amountInCents,
                    'product_data' => [
                        'name'        => $options['product_name'] ?? 'Marketplace Order',
                        'description' => $description,
                    ],
                ],
                'quantity' => 1,
            ]],

            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,

            'payment_intent_data' => [
                'application_fee_amount' => $platformFee,
                'transfer_data'          => [
                    'destination' => $destination,
                ],
                'metadata'    => $metadata,
                'description' => $description,
            ],

            'metadata' => $metadata,
        ]);

        $order = Order::create([
            'order_type'               => 'one_time',
            'tenant_id'                => $tenant->id,
            'user_id'                  => $buyer->id,
            'stripe_session_id'        => $session->id,
            'stripe_payment_intent_id' => $session->payment_intent ?? null,
            'amount'                   => $amountInCents,
            'currency'                 => $currency,
            'status'                   => 'created',
            'metadata'                 => $metadata,
            'raw_payload'              => $session->toArray(),
            'active'                   => true,
        ]);

        return [$order, $session];
    }

    /*
    |--------------------------------------------------------------------------
    | RECURRING SUBSCRIPTIONS (OnlyFans-style)
    |--------------------------------------------------------------------------
    |
    | $priceId = Stripe recurring Price ID (e.g. "price_123")
    */

    public function createSubscriptionCheckoutSession(
    Tenant $tenant,
    User $buyer,
    string $priceId,
    array $options = []
): array {
    $destination = $tenant->stripe_account_id;

    if (! $destination) {
        throw new \RuntimeException('Tenant has no Stripe Connect account configured.');
    }

    $successUrl = $options['success_url'] ?? $this->buildSuccessUrl($tenant);
    $cancelUrl  = $options['cancel_url']  ?? $this->buildCancelUrl($tenant);

    $metadata     = $this->buildMetadata($tenant, $buyer, $options['metadata'] ?? []);
    $description  = $options['description'] ?? "Subscription for tenant {$tenant->id}";
    $feePercent   = $this->getPlatformFeePercent();
    $orderCurrency = config('stripe_marketplace.currency', 'usd'); // 👈 use a real value

    $session = $this->stripe->checkout->sessions->create([
        'mode'                 => 'subscription',
        'payment_method_types' => ['card'],
        'client_reference_id'  => (string) $buyer->id,
        'customer_email'       => $buyer->email,

        'line_items' => [[
            'price'    => $priceId,
            'quantity' => 1,
        ]],

        'success_url' => $successUrl,
        'cancel_url'  => $cancelUrl,

        'subscription_data' => [
            'application_fee_percent' => $feePercent,
            'transfer_data'           => [
                'destination' => $destination,
            ],
            'metadata'    => $metadata,
            'description' => $description,
        ],

        'metadata' => $metadata,
    ]);

    // Optional: you can pull the actual currency from the session
    // $orderCurrency = $session->currency ?? $orderCurrency;

    $order = Order::create([
        'order_type'             => 'subscription',
        'tenant_id'              => $tenant->id,
        'user_id'                => $buyer->id,
        'stripe_session_id'      => $session->id,
        'stripe_subscription_id' => $session->subscription ?? null,
        'amount'                 => 0,                         // still fine for "meta" order
        'currency'               => $orderCurrency,            // 👈 NOT null anymore
        'status'                 => 'created',
        'metadata'               => $metadata,
        'raw_payload'            => $session->toArray(),
        'active'                 => true,
    ]);

    return [$order, $session];
}


    /*
    |--------------------------------------------------------------------------
    | Shared helpers
    |--------------------------------------------------------------------------
    */

    public function calculatePlatformFeeAmount(int $amountInCents): int
    {
        $percent = $this->getPlatformFeePercent();

        return (int) round($amountInCents * ($percent / 100));
    }

    public function getPlatformFeePercent(): float
    {
        return (float) config('stripe_marketplace.platform_fee_percent', 20);
    }

    protected function buildMetadata(Tenant $tenant, User $buyer, array $extra = []): array
    {
        return array_merge($extra, [
            'tenant_id'  => $tenant->id,
            'buyer_id'   => $buyer->id,
            'order_uuid' => (string) Str::uuid(),
        ]);
    }

    protected function buildSuccessUrl(Tenant $tenant): string
    {
        if (function_exists('route')) {
            return route('tenant.orders.success', ['tenant' => $tenant->id], true)
                . '?session_id={CHECKOUT_SESSION_ID}';
        }

        return rtrim(config('stripe_marketplace.success_url'), '/');
    }

    protected function buildCancelUrl(Tenant $tenant): string
    {
        if (function_exists('route')) {
            return route('tenant.orders.cancel', ['tenant' => $tenant->id], true);
        }

        return rtrim(config('stripe_marketplace.cancel_url'), '/');
    }
}