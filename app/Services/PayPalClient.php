<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class PayPalClient
{
    protected string $clientId;
    protected string $secret;
    protected string $baseUri;

    public function __construct()
    {
        $mode   = config('paypal.mode', 'sandbox'); // 'sandbox' or 'live'
        $config = config("paypal.{$mode}");

        $this->clientId = $config['client_id'];
        $this->secret   = $config['secret'];
        $this->baseUri  = $config['base_uri'];
    }

    protected function http(): Client
    {
        return new Client([
            'base_uri' => $this->baseUri,
            'timeout'  => 15,
        ]);
    }

    public function getAccessToken(): string
    {
        return Cache::remember('paypal_access_token', 50 * 60, function () {
            $response = $this->http()->post('/v1/oauth2/token', [
                'auth' => [$this->clientId, $this->secret],
                'form_params' => [
                    'grant_type' => 'client_credentials',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (! isset($data['access_token'])) {
                throw new RuntimeException('PayPal: no access token in response');
            }

            return $data['access_token'];
        });
    }

    protected function authHeaders(array $headers = []): array
    {
        return array_merge([
            'Authorization' => 'Bearer '.$this->getAccessToken(),
            'Content-Type'  => 'application/json',
        ], $headers);
    }

    /**
     * Create or update a Plan via PayPal Subscriptions API (simplified fixed-monthly).
     *
     * Uses standard billing plans API, not usage-based / commerce.
     */
    public function createOrUpdatePlan(?string $existingPlanId, float $amount, string $currency = 'USD'): array
    {
        $productId = env('PAYPAL_SUBSCRIPTION_PRODUCT_ID'); // set in .env

        $body = [
            'product_id' => $productId,
            'name'       => "Creator Monthly Subscription {$amount} {$currency}",
            'billing_cycles' => [
                [
                    'frequency' => [
                        'interval_unit'  => 'MONTH',
                        'interval_count' => 1,
                    ],
                    'tenure_type' => 'REGULAR',
                    'sequence'    => 1,
                    'total_cycles'=> 0, // infinite
                    'pricing_scheme' => [
                        'fixed_price' => [
                            'value'         => number_format($amount, 2, '.', ''),
                            'currency_code' => $currency,
                        ],
                    ],	
                ],
            ],
            'payment_preferences' => [
                'auto_bill_outstanding'     => true,
                'setup_fee_failure_action'  => 'CANCEL',
                'payment_failure_threshold' => 3,
            ],
        ];

        // For now we always POST a new plan (you could PATCH when $existingPlanId is set)
        $response = $this->http()->post('/v1/billing/plans', [
            'headers' => $this->authHeaders(),
            'body'    => json_encode($body),
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Create a subscription and return raw response (includes approval link).
     */
    public function createSubscription(string $planId, string $returnUrl, string $cancelUrl): array
    {
        $body = [
            'plan_id' => $planId,
            'application_context' => [
                'brand_name'          => config('app.name'),
                'return_url'          => $returnUrl,
                'cancel_url'          => $cancelUrl,
                'user_action'         => 'SUBSCRIBE_NOW',
                'shipping_preference' => 'NO_SHIPPING',
            ],
        ];

        $response = $this->http()->post('/v1/billing/subscriptions', [
            'headers' => $this->authHeaders(),
            'body'    => json_encode($body),
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get subscription details (status, billing_info, next_billing_time, etc.).
     */
    public function getSubscription(string $paypalSubscriptionId): array
    {
        $response = $this->http()->get("/v1/billing/subscriptions/{$paypalSubscriptionId}", [
            'headers' => $this->authHeaders(),
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Create a PayPal Payout from platform (landlord) to creator (80% share).
     */
    public function createPayout(string $creatorEmail, float $amount, string $currency = 'USD', string $note = ''): array
    {
        $body = [
            'sender_batch_header' => [
                'sender_batch_id' => 'payout_'.uniqid(),
                'email_subject'   => 'You have a payout',
            ],
            'items' => [
                [
                    'recipient_type' => 'EMAIL',
                    'amount' => [
                        'value'    => number_format($amount, 2, '.', ''),
                        'currency' => $currency,
                    ],
                    'receiver' => $creatorEmail,
                    'note'     => $note,
                ],
            ],
        ];

        $response = $this->http()->post('/v1/payments/payouts', [
            'headers' => $this->authHeaders(),
            'body'    => json_encode($body),
        ]);
	
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Optional helper: create a Product via PayPal Catalogs API.
     */
    public function createProduct(
        string $name = 'Creator Subscription',
        string $description = 'Monthly creator subscription'
    ): array {
        $body = [
            'name'        => $name,
            'description' => $description,
            'type'        => 'SERVICE',
            'category'    => 'SOFTWARE', // adjust if you want
        ];

        $response = $this->http()->post('/v1/catalogs/products', [
            'headers' => $this->authHeaders(),
            'body'    => json_encode($body),
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Cancel a PayPal subscription using the STANDARD Subscriptions API.
     *
     * $cancelOption is only used by your own code ("IMMEDIATE" vs "END_OF_PERIOD");
     * PayPal just cancels immediately.
     */
    public function cancelSubscription(string $subscriptionId, string $cancelOption = 'IMMEDIATE', ?string $reason = null): void
    {
        $body = [];

        if ($reason) {
            $body['reason'] = $reason;
        }

        $this->http()->post(
            "/v1/billing/subscriptions/{$subscriptionId}/cancel",
            [
                'headers' => $this->authHeaders(),
                'body'    => json_encode($body),
            ]
        );
    }
}
