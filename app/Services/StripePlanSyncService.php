<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenant;
use Stripe\StripeClient;

class StripePlanSyncService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create or sync Stripe Product + recurring Price for this plan.
     *
     * - Creates Product if missing
     * - Creates a new Price if missing OR if pricing terms changed
     * - Updates plan->stripe_product_id and plan->stripe_price_id
     */
    public function syncProductAndPrice(Tenant $tenant, Plan $plan, array $opts = []): Plan
    {
        // You can require onboarded_at if you want stricter gating.
        if (! $tenant->stripe_account_id) {
            throw new \RuntimeException('Tenant has no Stripe Connect account configured.');
        }

        // Basic “terms” used to decide if we must create a NEW Stripe Price
        $currency = strtolower($plan->currency ?: 'usd');
        $interval = $plan->interval ?: 'month';
        $amount   = (int) $plan->amount; // cents

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Plan amount must be set in cents and > 0.');
        }

        // 1) Ensure Product exists
        if (! $plan->stripe_product_id) {
            $product = $this->stripe->products->create([
                'name'        => $plan->name,
                'description' => $plan->description,
                'metadata'    => array_merge([
                    'tenant_id' => $tenant->id,
                    'plan_id'   => $plan->id,
                ], $opts['metadata'] ?? []),
            ]);

            $plan->stripe_product_id = $product->id;
        } else {
            // Keep Product name/description in sync (optional but useful)
            $this->stripe->products->update($plan->stripe_product_id, [
                'name'        => $plan->name,
                'description' => $plan->description,
            ]);
        }

        // 2) Decide whether to create a NEW Price
        $mustCreateNewPrice = false;

        if (! $plan->stripe_price_id) {
            $mustCreateNewPrice = true;
        } else {
            // Retrieve current price to compare terms
            $price = $this->stripe->prices->retrieve($plan->stripe_price_id, []);
            $existingAmount   = (int) ($price->unit_amount ?? 0);
            $existingCurrency = strtolower((string) ($price->currency ?? 'usd'));
            $existingInterval = (string) (($price->recurring->interval ?? null) ?: 'month');

            if ($existingAmount !== $amount || $existingCurrency !== $currency || $existingInterval !== $interval) {
                $mustCreateNewPrice = true;
            }
        }

        // 3) Create new recurring price if needed
        if ($mustCreateNewPrice) {
            $newPrice = $this->stripe->prices->create([
                'product'     => $plan->stripe_product_id,
                'unit_amount' => $amount,
                'currency'    => $currency,
                'recurring'   => [
                    'interval' => $interval, // month|year|week|day
                ],
                'metadata' => array_merge([
                    'tenant_id' => $tenant->id,
                    'plan_id'   => $plan->id,
                ], $opts['metadata'] ?? []),
            ]);

            // Optional: deactivate old price so it’s not used accidentally
            if ($plan->stripe_price_id) {
                try {
                    $this->stripe->prices->update($plan->stripe_price_id, ['active' => false]);
                } catch (\Throwable $e) {
                    // ignore; deactivation isn't critical
                }
            }

            $plan->stripe_price_id = $newPrice->id;
        }

        $plan->save();

        return $plan;
    }
}