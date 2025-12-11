<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\StripeMarketplaceOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantOrderController extends Controller
{
    public function create(Request $request, Tenant $tenant): View
    {
        return view('tenant.orders.create', [
            'tenant' => $tenant,
        ]);
    }

    public function checkout(
        Request $request,
        Tenant $tenant,
        StripeMarketplaceOrderService $orders
    ): RedirectResponse {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'], // $1 minimum; adjust as needed
        ]);

        $user = $request->user();

        [$order, $session] = $orders->createOneTimeOrderCheckoutSession(
            tenant: $tenant,
            buyer:  $user,
            amount: $data['amount'],
            currency: null,
            options: [
                'description' => "One-time support for {$tenant->name}",
            ]
        );

        return redirect()->away($session->url);
    }

    public function success(Request $request, Tenant $tenant): View
    {
        $sessionId = $request->query('session_id');

        // Optional: fetch the order here and display info.
        return view('tenant.orders.success', [
            'tenant'     => $tenant,
            'session_id' => $sessionId,
        ]);
    }

    public function cancel(Request $request, Tenant $tenant): View
    {
        return view('tenant.orders.cancel', [
            'tenant' => $tenant,
        ]);
    }
}