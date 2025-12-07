<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\StripePaymentService;

class StripeCheckoutController extends Controller
{
    public function __construct(
        protected StripePaymentService $stripePayment
    ) {}

    public function start(Order $order)
    {
        $tenant = $order->tenant; // relationship: Order belongsTo Tenant

        if (! $tenant->stripe_account_id) {
            return back()->with('error', 'Tenant is not onboarded to Stripe.');
        }

        $url = $this->stripePayment->createCheckoutForTenant($order, $tenant);

        return redirect()->away($url);
    }

    public function success(Order $order)
    {
        // For safety, verify via webhook instead of query param.
        // Here you can show a "Thank you" page; actual status update handled by webhook.
        return view('payments.success', compact('order'));
    }

    public function cancel(Order $order)
    {
        return redirect()->route('orders.show', $order)->with('error', 'Payment cancelled.');
    }
}