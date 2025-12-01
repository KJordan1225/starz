<?php

namespace App\Services;

use App\Models\Order;

class TenantService {
    
    public function getTenantId() {
        // Get Tenancy manager
        $tenancy = tenancy();

        // Default
        $tenant_id = null;

        // Already initialized
        if ($tenancy->initialized) {
            $tenant_id = $tenancy->tenant?->id;
        } else {
            // Prefer route param `{tenant}`, fall back to first segment
            $segment = request()->route('tenant') ?? request()->segment(1);

            if ($segment) {
                $tenant = Tenant::query()
                    ->where('id', $segment)
                    // ->orWhere('slug', $segment)
                    ->first();

                if ($tenant) {
                    $tenancy->initialize($tenant);
                    $tenant_id = $tenant->id;
                }
                // else: silently ignore non-tenant segment
            }
        }
        
        return $tenant_id;
    }
}