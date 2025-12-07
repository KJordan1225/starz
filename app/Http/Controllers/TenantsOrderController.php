<?php

use App\Models\Tenant;
use App\Services\StripeMarketplaceOrderService;
use Illuminate\Http\Request;

class TenantOrderController
{
    public function createCheckoutSession(
        Request $request,
        Tenant $tenant,
        StripeMarketplaceOrderService $orders
    ) {
        $user   = $request->user();
        $amount = 9.99; // or from request / DB

        [$order, $session] = $orders->createOrderCheckoutSession(
            tenant:  $tenant,
            buyer:   $user,
            amount:  $amount,
            currency: 'usd',
            options: [
                'product_name' => 'Creator Tip',
                'description'  => "Tip for {$tenant->name}",
                'metadata'     => [
                    'type' => 'tip',
                ],
            ]
        );

        // Redirect the user to Stripe Checkout
        return redirect()->away($session->url);
    }
}
