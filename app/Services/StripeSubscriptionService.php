<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeSubscriptionService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Ensure the given plan is represented in Stripe as a Product + Price.
     */
    public function syncPlanToStripe(SubscriptionPlan $plan, Tenant $tenant): void
    {
        // If plan already has a Stripe price, see if we can re-use it
        if ($plan->stripe_price_id) {
            $this->syncExistingStripePrice($plan, $tenant);
            return;
        }

        // 1) No stripe_price_id yet → create Product + Price
        $product = $this->stripe->products->create([
            'name'        => $plan->name,
            'description' => $plan->description ?: null,
            'metadata'    => [
                'tenant_id' => $tenant->id,
                'plan_id'   => $plan->id,
            ],
        ]);

        $price = $this->stripe->prices->create([
            'unit_amount' => $plan->amount, // cents
            'currency'    => $plan->currency,
            'recurring'   => [
                'interval' => $plan->interval, // 'month' or 'year'
            ],
            'product'     => $product->id,
            'metadata'    => [
                'tenant_id' => $tenant->id,
                'plan_id'   => $plan->id,
            ],
        ]);

        $plan->stripe_price_id = $price->id;
        $plan->save();
    }

    protected function syncExistingStripePrice(SubscriptionPlan $plan, Tenant $tenant): void
    {
        try {
            $price = $this->stripe->prices->retrieve($plan->stripe_price_id, []);
        } catch (ApiErrorException $e) {
            // If Stripe says it's gone/invalid, treat as if none exists.
            $plan->stripe_price_id = null;
            $plan->save();

            $this->syncPlanToStripe($plan, $tenant);
            return;
        }

        $productId = is_string($price->product)
            ? $price->product
            : $price->product->id;

        $currentAmount   = (int) $price->unit_amount;
        $currentCurrency = strtolower($price->currency);
        $currentInterval = $price->recurring?->interval ?? null;

        $changed = false;

        if ($currentAmount !== (int) $plan->amount) {
            $changed = true;
        }

        if ($currentCurrency !== strtolower($plan->currency)) {
            $changed = true;
        }

        if ($currentInterval !== $plan->interval) {
            $changed = true;
        }

        if (! $changed) {
            $this->maybeUpdateStripeProduct($productId, $plan, $tenant);
            return;
        }

        // 2) Price changed → create NEW Price
        $newPrice = $this->stripe->prices->create([
            'unit_amount' => $plan->amount,
            'currency'    => $plan->currency,
            'recurring'   => [
                'interval' => $plan->interval,
            ],
            'product'     => $productId,
            'metadata'    => [
                'tenant_id' => $tenant->id,
                'plan_id'   => $plan->id,
            ],
        ]);

        // Optionally deactivate old price:
        // $this->stripe->prices->update($price->id, ['active' => false]);

        $plan->stripe_price_id = $newPrice->id;
        $plan->save();
    }

    protected function maybeUpdateStripeProduct(string $productId, SubscriptionPlan $plan, Tenant $tenant): void
    {
        $this->stripe->products->update($productId, [
            'name'        => $plan->name,
            'description' => $plan->description ?: null,
            'metadata'    => [
                'tenant_id' => $tenant->id,
                'plan_id'   => $plan->id,
            ],
        ]);
    }
}
