<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\TenantService;

class TenantSubscribeReturnController extends Controller
{
    public function success(Request $request): View
    {
        $tenantService = new TenantService();
        $tenantId = $tenantService->getTenantId();
        $tenant = Tenant::find($tenantId);

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