<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Tenant;
use App\Services\StripePlanSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantPlanAdminController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $plans = Plan::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('featured')
            ->orderBy('amount')
            ->get();

        return view('tenant.admin.plans.index', [
            'tenant' => $tenant,
            'plans'  => $plans,
        ]);
    }

    public function create(Request $request, Tenant $tenant): View
    {
        return view('tenant.admin.plans.create', [
            'tenant' => $tenant,
        ]);
    }

    public function store(
        Request $request,
        Tenant $tenant,
        StripePlanSyncService $sync
    ): RedirectResponse {

        // disallow store function if not onboarded
        abort_unless($tenant->stripe_account_id, 400, 'Connect Stripe first before creating plans.');

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'amount'      => ['required', 'integer', 'min:100'], // cents; min $1.00
            'currency'    => ['required', 'string', 'size:3'],
            'interval'    => ['required', 'in:day,week,month,year'],
            'active'      => ['nullable', 'boolean'],
            'featured'    => ['nullable', 'boolean'],
        ]);

        $plan = Plan::create([
            'tenant_id'    => $tenant->id,
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'amount'       => (int) $data['amount'],
            'currency'     => strtolower($data['currency']),
            'interval'     => $data['interval'],
            'active'       => (bool) ($data['active'] ?? true),
            'featured'     => (bool) ($data['featured'] ?? false),
        ]);

        // Create Stripe Product + Price and store IDs on the Plan
        $sync->syncProductAndPrice($tenant, $plan);

        return redirect()
            ->route('tenant.admin.plans.index', ['tenant' => $tenant->id])
            ->with('success', 'Plan created and synced to Stripe.');
    }

    public function edit(Request $request, Tenant $tenant, Plan $plan): View
    {
        abort_unless((string) $plan->tenant_id === (string) $tenant->id, 404);

        return view('tenant.admin.plans.edit', [
            'tenant' => $tenant,
            'plan'   => $plan,
        ]);
    }

    public function update(
        Request $request,
        Tenant $tenant,
        Plan $plan,
        StripePlanSyncService $sync
    ): RedirectResponse {

         // disallow update function if not onboarded
        abort_unless($tenant->stripe_account_id, 400, 'Connect Stripe first before updating plans.');

        abort_unless((string) $plan->tenant_id === (string) $tenant->id, 404);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'amount'      => ['required', 'integer', 'min:100'], // cents
            'currency'    => ['required', 'string', 'size:3'],
            'interval'    => ['required', 'in:day,week,month,year'],
            'active'      => ['nullable', 'boolean'],
            'featured'    => ['nullable', 'boolean'],
        ]);

        $plan->fill([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'amount'      => (int) $data['amount'],
            'currency'    => strtolower($data['currency']),
            'interval'    => $data['interval'],
            'active'      => (bool) ($data['active'] ?? false),
            'featured'    => (bool) ($data['featured'] ?? false),
        ])->save();

        // If pricing terms changed, service creates a new Stripe Price automatically
        $sync->syncProductAndPrice($tenant, $plan);

        return redirect()
            ->route('tenant.admin.plans.index', ['tenant' => $tenant->id])
            ->with('success', 'Plan updated and synced to Stripe.');
    }
}