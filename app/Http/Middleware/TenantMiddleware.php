<?php

namespace App\Http\Middleware;

use Closure;
use Stancl\Tenancy\Tenancy;
use Illuminate\Http\Request;
use Stancl\Tenancy\Database\Models\Tenant;

class TenantMiddleware
{
    public function __construct(protected Tenancy $tenancy) {}

    public function handle(Request $request, Closure $next)
    {
        // assumes your routes use prefix('{tenant}')
        $tenantId = $request->route('tenant');

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                abort(404, 'Tenant not found');
            }
            $this->tenancy->initialize($tenant);
        }

        try {
            return $next($request);
        } finally {
            if ($this->tenancy->initialized) {
                $this->tenancy->end();
            }
        }
    }
}