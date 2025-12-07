<?php

namespace App\Services;

use Stripe\Account;
use Stripe\AccountLink;
use App\Models\Tenant;
use Stripe\Stripe;

class StripeConnectService
{
    public function createOrRetrieveAccount(Tenant $tenant): Tenant
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        if ($tenant->stripe_account_id) {
            return $tenant;
        }

        $type = config('services.stripe.connect_type', 'express');

        $account = Account::create([
            'type' => $type,
            'country' => 'US',
            'email' => $tenant->email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers'     => ['requested' => true],
            ],
            'business_type' => 'individual',
        ]);

        $tenant->stripe_account_id = $account->id;
        $tenant->save();

        return $tenant;
    }

    
    public function createOnboardingLink(Tenant $tenant): string
    {
        $this->createOrRetrieveAccount($tenant);

        $accountLink = AccountLink::create([
            'account'     => $tenant->stripe_account_id,
            'refresh_url' => route('stripe.tenant.onboarding.refresh', $tenant),
            'return_url'  => route('stripe.tenant.onboarding.return', $tenant),
            'type'        => 'account_onboarding',
        ]);

        return $accountLink->url;
    }
}