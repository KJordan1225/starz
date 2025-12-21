<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
          
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        }

        match ($event->type) {
            'checkout.session.completed'        => $this->handleCheckoutSessionCompleted($event->data->object),
            'customer.subscription.created',
            'customer.subscription.updated'     => $this->syncSubscription($event->data->object),
            'customer.subscription.deleted'     => $this->handleSubscriptionDeleted($event->data->object),
            default                              => null,
        };

        return response('OK', 200);
    }


    protected function handleCheckoutSessionCompleted($session): void
    {
        // Only subscription checkouts
        if ($session->mode !== 'subscription') {
            return;
        }

        $order = Order::where('stripe_session_id', $session->id)->first();

        if (! $order) {
            return;
        }

        $order->update([
            'stripe_customer_id'      => $session->customer,
            'stripe_subscription_id'  => $session->subscription,
            'status'                  => 'active', // provisional until subscription event
            'raw_payload'             => $session->toArray(),
        ]);
    }


    protected function syncSubscription($subscription): void
    {
        $order = StripeOrder::where('stripe_subscription_id', $subscription->id)->first();

        if (! $order) {
            return;
        }

        $priceId = $subscription->items->data[0]->price->id ?? null;

        $order->update([
            'stripe_customer_id'      => $subscription->customer,
            'stripe_price_id'         => $priceId,

            'status'                  => $subscription->status,
            'cancel_at_period_end'    => $subscription->cancel_at_period_end,

            'current_period_start'    => $subscription->current_period_start
                ? now()->setTimestamp($subscription->current_period_start)
                : null,

            'current_period_end'      => $subscription->current_period_end
                ? now()->setTimestamp($subscription->current_period_end)
                : null,

            'canceled_at'             => $subscription->canceled_at
                ? now()->setTimestamp($subscription->canceled_at)
                : null,

            'raw_payload'             => $subscription->toArray(),
        ]);
    }


    protected function handleSubscriptionDeleted($subscription): void
    {
        $order = StripeOrder::where('stripe_subscription_id', $subscription->id)->first();

        if (! $order) {
            return;
        }

        $order->update([
            'status'      => 'canceled',
            'canceled_at' => now(),
            'raw_payload' => $subscription->toArray(),
        ]);
    }

}