<?php

namespace App\Services;

use App\Models\StripeOrder;
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
     * Create a Checkout Session for a marketplace order.
     *
     * @param  Tenant      $tenant   The creator/seller's tenant
     * @param  User        $buyer    The platform user buying
     * @param  int|float   $amount   Amount in major units (e.g. 9.99 for $9.99)
     * @param  string|null $currency
     * @param  array       $options  Extra options/overrides (description, metadata, success/cancel URLs)
     * @return array [StripeOrder $order, \Stripe\Checkout\Session $session]
     */
    public function createOrderCheckoutSession(
        Tenant $tenant,
        User $buyer,
        int|float $amount,
        ?string $currency = null,
        array $options = []
    ): array {
        $currency = $currency ?: config('stripe_marketplace.currency', 'usd');

        // Convert to smallest currency unit (e.g. cents)
        $amountInCents = (int) round($amount * 100);

        // Calculate platform fee & creator share
        $platformFeeAmount = $this->calculatePlatformFeeAmount($amountInCents);
        $creatorStripeAccountId = $tenant->stripe_account_id;

        if (! $creatorStripeAccountId) {
            throw new \RuntimeException("Tenant has no Stripe Connect account configured.");
        }

        // Build success/cancel URLs (can be tenant-aware)
        $successUrl = $options['success_url']
            ?? $this->buildSuccessUrl($tenant);
        $cancelUrl = $options['cancel_url']
            ?? $this->buildCancelUrl($tenant);

        // Optional metadata
        $metadata = $options['metadata'] ?? [];
        $metadata = array_merge($metadata, [
            'tenant_id' => $tenant->id,
            'buyer_id'  => $buyer->id,
            'order_id'  => (string) Str::uuid(),
        ]);

        $description = $options['description']
            ?? "Order for tenant {$tenant->id}";

        // 1) Create the Checkout Session (destination charge via payment_intent_data)
        $session = $this->stripe->checkout->sessions->create([
            'mode'               => 'payment',
            'payment_method_types' => ['card'],
            'client_reference_id'  => (string) $buyer->id,
            'customer_email'       => $buyer->email, // or use a stored Stripe customer

            'line_items' => [
                [
                    'price_data' => [
                        'currency'     => $currency,
                        'unit_amount'  => $amountInCents,
                        'product_data' => [
                            'name'        => $options['product_name'] ?? 'Marketplace Order',
                            'description' => $description,
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],

            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,

            // This is where the marketplace magic happens:
            'payment_intent_data' => [
                'application_fee_amount' => $platformFeeAmount,
                'transfer_data'          => [
                    'destination' => $creatorStripeAccountId,
                ],
                'metadata' => $metadata,
                'description' => $description,
            ],

            'metadata' => $metadata,
        ]);

        // 2) Persist order locally (stripe_orders table)
        $order = StripeOrder::create([
            'tenant_id'                => $tenant->id,
            'user_id'                  => $buyer->id,
            'stripe_session_id'        => $session->id,
            'stripe_payment_intent_id' => $session->payment_intent ?? null,
            'amount'                   => $amountInCents,
            'currency'                 => $currency,
            'status'                   => 'created',
            'metadata'                 => $metadata,
            'raw_payload'              => $session->toArray(),
        ]);

        return [$order, $session];
    }

    /**
     * Calculate the platform fee amount in the smallest currency unit (e.g. cents).
     */
    public function calculatePlatformFeeAmount(int $amountInCents): int
    {
        $percent = (float) config('stripe_marketplace.platform_fee_percent', 20);

        return (int) round($amountInCents * ($percent / 100));
    }

    /**
     * Build a tenant-aware success URL.
     */
    protected function buildSuccessUrl(Tenant $tenant): string
    {
        // Example if you have a named route:
        if (function_exists('route')) {
            return route('tenant.orders.success', ['tenant' => $tenant->id, 'session_id' => '{CHECKOUT_SESSION_ID}'], true);
        }

        // Fallback
        return rtrim(config('stripe_marketplace.success_url'), '/');
    }

    /**
     * Build a tenant-aware cancel URL.
     */
    protected function buildCancelUrl(Tenant $tenant): string
    {
        if (function_exists('route')) {
            return route('tenant.orders.cancel', ['tenant' => $tenant->id], true);
        }

        return rtrim(config('stripe_marketplace.cancel_url'), '/');
    }
}
