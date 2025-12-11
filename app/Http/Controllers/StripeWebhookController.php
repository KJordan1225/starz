<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload    = $request->getContent();
        $sigHeader  = $request->header('Stripe-Signature');
        $secret     = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $secret
            );
        } catch (SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event->data->object);
                break;

            case 'invoice.payment_succeeded':
                // Optional: track recurring invoices as additional order records or logs
                $this->handleInvoicePaymentSucceeded($event->data->object);
                break;
        }

        return response('OK', 200);
    }

    protected function handleCheckoutSessionCompleted($session): void
    {
        // $session is \Stripe\Checkout\Session
        $order = Order::where('stripe_session_id', $session->id)->first();

        if (! $order) {
            return;
        }

        if ($order->order_type === 'subscription') {
            // subscription ID should now be present
            $order->stripe_subscription_id = $session->subscription ?? $order->stripe_subscription_id;
        } else {
            // one-time payment
            $order->stripe_payment_intent_id = $session->payment_intent ?? $order->stripe_payment_intent_id;
        }

        $order->status  = 'succeeded';
        $order->paid_at = now();
        $order->save();
    }

    protected function handleInvoicePaymentSucceeded($invoice): void
    {
        // $invoice is \Stripe\Invoice
        // subscription ID is here for recurring charges
        $subscriptionId = $invoice->subscription ?? null;

        if (! $subscriptionId) {
            return;
        }

        // You can either:
        // 1) Just log it / update a "last_paid_at" on a Subscription model
        // 2) Or create a new StripeOrder row per invoice if you want a full ledger

        // Example: mark "last_paid_at" on the original order (if you want)
        $order = Order::where('stripe_subscription_id', $subscriptionId)->first();

        if ($order) {
            // Keep status as "succeeded" but update paid_at to latest invoice
            $order->paid_at = now();
            $order->save();
        }
    }
}