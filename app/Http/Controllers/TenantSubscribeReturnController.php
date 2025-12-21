<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\TenantService;
use App\Models\Order;

class TenantSubscribeReturnController extends Controller
{
    public function success(Request $request): View
    {
        $tenantService = new TenantService();
        $tenantId = $tenantService->getTenantId();
        $tenant = Tenant::find($tenantId);

        // Get stripe_subscription_id        
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        $session = \Stripe\Checkout\Session::retrieve(
            request('session_id')
        );
        $subscriptionId = $session->subscription; 

        // update order record
        $order = Order::where('stripe_session_id', $session->id)->first();        

        $order->update([
            'stripe_customer_id'      => $session->customer,
            'stripe_subscription_id'  => $subscriptionId,
            'status'                  => 'active', // provisional until subscription event
            'raw_payload'             => $session->toArray(),
        ]);
    

        return view('tenant.subscribe.success', [
            'tenant' => $tenant,
            'session_id' => $request->query('session_id'),
        ]);
    }

    public function cancel(Request $request): View
    {
        $tenantService = new TenantService();
        $tenantId = $tenantService->getTenantId();

        $tenant = Tenant::find($tenantId);
        
        return view('tenant.subscribe.cancel', [
            'tenant' => $tenant,
        ]);
    }
}