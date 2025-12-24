@extends('layouts.landlord')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Subscription Plans</h3>

        <a href="{{ route('tenant.admin.plans.create', ['tenant' => $tenant->id]) }}"
           class="btn btn-primary">
            + Create Plan
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Plan</th>
                        <th>Price</th>
                        <th>Stripe</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($plans as $plan)
                    <tr>
                        <td>
                            <div class="fw-semibold">
                                {{ $plan->name }}
                                @if($plan->featured)
                                    <span class="badge bg-primary ms-2">Featured</span>
                                @endif
                            </div>
                            @if($plan->description)
                                <div class="text-muted small">{{ $plan->description }}</div>
                            @endif
                        </td>

                        <td>
                            <span class="fw-semibold">
                                ${{ number_format(($plan->price ?? 0), 2) }}
                            </span>
                            <span class="text-muted">/ {{ ucfirst($plan->interval) }}</span>
                            <div class="text-muted small">{{ strtoupper($plan->currency) }}</div>
                        </td>

                        <td class="small">
                            <div>prod: <code>{{ $plan->stripe_product_id ?: '—' }}</code></div>
                            <div>price: <code>{{ $plan->stripe_price_id ?: '—' }}</code></div>
                        </td>

                        <td>
                            @if($plan->active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>

                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary"
                               href="{{ route('tenant.admin.plans.edit', ['tenant' => $tenant->id, 'plan' => $plan->id]) }}">
                                Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            No plans yet.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection