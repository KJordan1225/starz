<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Services\PayPalClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayPalWebhookController extends Controller
{
    public function handle(Request $request, PayPalClient $paypal)
    {
        $event = $request->all();

        Log::info('PayPal Webhook received', ['event' => $event]);

        $eventType = $event['event_type'] ?? null;

        if ($eventType === 'BILLING.SUBSCRIPTION.PAYMENT.SUCCEEDED') {
            return $this->handleSubscriptionPaymentSucceeded($event, $paypal);
        }

        // Acknowledge others.
        return response()->json(['status' => 'ok']);
    }

    protected function handleSubscriptionPaymentSucceeded(array $event, PayPalClient $paypal)
    {
        $resource = $event['resource'] ?? [];

        $paypalSubscriptionId = $resource['id'] ?? $resource['subscription_id'] ?? null;
        if (! $paypalSubscriptionId) {
            Log::warning('Missing PayPal subscription ID in webhook.', ['event' => $event]);
            return response()->json(['status' => 'missing_id'], 400);
        }

        // PayPal billing info path: resource.billing_info.last_payment.amount
        $billingInfo   = $resource['billing_info'] ?? [];
        $lastPayment   = $billingInfo['last_payment'] ?? [];
        $amount        = $lastPayment['amount']['value'] ?? null;
        $currency      = $lastPayment['amount']['currency_code'] ?? 'USD';
        $transactionId = $lastPayment['transaction_id'] ?? null;

        if (! $amount) {
            Log::warning('Missing amount in payment webhook.', ['event' => $event]);
            return response()->json(['status' => 'missing_amount'], 400);
        }

        $gross       = (float) $amount;
        $platform    = round($gross * 0.20, 2); // 20%
        $creatorShare= round($gross * 0.80, 2); // 80%

        $subscription = Subscription::where('paypal_subscription_id', $paypalSubscriptionId)->first();

        if (! $subscription) {
            Log::warning('Subscription not found for PayPal subscription id.', ['paypal_subscription_id' => $paypalSubscriptionId]);
            return response()->json(['status' => 'subscription_not_found'], 404);
        }

        // Log payment locally
        $payment = SubscriptionPayment::create([
            'subscription_id'        => $subscription->id,
            'paypal_subscription_id' => $paypalSubscriptionId,
            'paypal_transaction_id'  => $transactionId,
            'currency'               => $currency,
            'gross_amount'           => $gross,
            'platform_share'         => $platform,
            'creator_share'          => $creatorShare,
        ]);

        // Find tenant payout email
        $tenant = Tenant::find($subscription->tenant_id);

        if (! $tenant || ! $tenant->paypal_payout_email) {
            Log::warning('No payout email configured for tenant.', [
                'tenant_id' => $subscription->tenant_id,
            ]);

            return response()->json(['status' => 'no_payout_email'], 200);
        }

        // Payout creator share via PayPal Payouts API
        try {
            $payout = $paypal->createPayout(
                $tenant->paypal_payout_email,
                $creatorShare,
                $currency,
                "Subscription payout for subscription #{$subscription->id}"
            );

            $batchId = $payout['batch_header']['payout_batch_id'] ?? null;

            $payment->update([
                'creator_payout_batch_id' => $batchId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error creating PayPal payout for creator.', [
                'error'  => $e->getMessage(),
                'tenant' => $tenant->id ?? null,
            ]);
        }

        return response()->json(['status' => 'success']);
    }
}
