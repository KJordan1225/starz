<?php

namespace App\Services;

use App\Models\Tenant;
use Stripe\StripeClient;

class StripeConnectOnboardingService
{
    public function __construct(protected StripeClient $stripe)
    {
        // Let Laravel inject StripeClient, or create it here if you prefer.
        // If you prefer no DI: $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public static function make(): self
    {
        return new self(new StripeClient(config('services.stripe.secret')));
    }

    /**
     * Ensure tenant has a Stripe Connect account.
     */
    public function ensureAccount(Tenant $tenant): Tenant
    {
        if ($tenant->stripe_account_id) {
            return $tenant;
        }
        
        $account = $this->stripe->accounts->create([
            'type' => 'express', // express recommended for creators
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers'     => ['requested' => true],
            ],
            'metadata' => [
                'tenant_id' => $tenant->id,
            ],
            // optional:
            // 'business_profile' => ['url' => 'https://example.com'],
        ]);

        $tenant->forceFill([
            'stripe_account_id' => $account->id,
        ])->save(); 

        return $tenant;
    }

    /**
     * Create an onboarding link (Account Link) and return the URL.
     */
    public function createOnboardingLink(Tenant $tenant, string $refreshUrl, string $returnUrl): string
    {
        $tenant = $this->ensureAccount($tenant);

        $link = $this->stripe->accountLinks->create([
            'account'     => $tenant->stripe_account_id,
            'refresh_url' => $refreshUrl,
            'return_url'  => $returnUrl,
            'type'        => 'account_onboarding',
        ]);

        return $link->url;
    }

    /**
     * Optional: verify whether onboarding is complete (basic).
     * You can tighten this based on your requirements.
     */
    public function syncOnboardingStatus(Tenant $tenant): Tenant
    {
        if (! $tenant->stripe_account_id) {
            return $tenant;
        }

        $account = $this->stripe->accounts->retrieve($tenant->stripe_account_id, []);

        // Common “good enough” signal: charges_enabled or payouts_enabled
        if (! $tenant->stripe_onboarded_at && (($account->charges_enabled ?? false) || ($account->payouts_enabled ?? false))) {
            $tenant->forceFill(['stripe_onboarded_at' => now()])->save();
        }

        return $tenant;
    }
}