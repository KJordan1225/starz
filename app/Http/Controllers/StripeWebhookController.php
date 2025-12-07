<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Event as StripeEvent;
use App\Models\Order;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload    = $request->getContent();
        $sigHeader  = $request->header('Stripe-Signature');
        $secret     = config('stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                /** @var \Stripe\Checkout\Session $session */
                $session = $event->data->object;

                // Example: find order by session id
                $order = Order::where('stripe_session_id', $session->id)->first();

                if ($order && $session->payment_status === 'paid') {
                    $order->update([
                        'status'          => 'paid',
                        'stripe_payment_intent' => $session->payment_intent,
                    ]);
                }
                break;

            case 'payment_intent.payment_failed':
                // handle failures if needed
                break;
        }

        return response('Webhook handled', 200);
    }
}