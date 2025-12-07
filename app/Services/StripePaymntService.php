<?php

namespace App\Services;

use Stripe\Checkout\Session as CheckoutSession;
use App\Models\Tenant;
use App\Models\Order;

class StripePaymentService
{
    public function createCheckoutForTenant(Order $order, Tenant $tenant): string
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        
        $amount   = (int) round($order->total_amount * 100); // in cents
        $currency = config('stripe.currency', 'usd');

        // $platformFeePercent = config('stripe.app_fee_percent');
        $platformFeePercent = 20;
        $feeAmount          = (int) round($amount * ($platformFeePercent / 100));

        $session = CheckoutSession::create([
            'mode'        => 'payment',
            'payment_method_types' => ['card'],
            'line_items'  => [[
                'quantity'   => 1,
                'price_data' => [
                    'currency'     => $currency,
                    'product_data' => [
                        'name' => $order->title ?? 'Order #' . $order->id,
                    ],
                    'unit_amount'  => $amount,
                ],
            ]],
            'success_url' => route('stripe.checkout.success', ['order' => $order, 'session_id' => '{CHECKOUT_SESSION_ID}']),
            'cancel_url'  => route('stripe.checkout.cancel', ['order' => $order]),
            // Connect-specific bits:
            'payment_intent_data' => [
                'application_fee_amount' => $feeAmount,
                'transfer_data'          => [
                    'destination' => $tenant->stripe_account_id, // tenant gets net
                ],
            ],
        ]);

        // store session for verification
        $order->update([
            'stripe_session_id' => $session->id,
            'stripe_amount'     => $amount,
        ]);

        return $session->url;
    }
}