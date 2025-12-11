@extends('layouts.landlord')

@section('content')
@php
    use App\Services\TenantService;
    $tenantService = new TenantService();
    $tenantId = $tenantService->getTenantId();
@endphp
<div class="container py-5">
    <h2 class="mb-4">Subscribe to {{ $tenant->name }}</h2>

    @if($plans->isEmpty())
        <div class="alert alert-info">
            No subscription plans are available at this time.
        </div>
    @else
        <div class="row">
            @foreach($plans as $plan)
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">{{ $plan->name }}</h5>
                            <p class="card-text mb-1">
                                <strong>${{ number_format($plan->amount / 100, 2) }}</strong>
                                / {{ ucfirst($plan->interval) }}
                            </p>
                            <p class="text-muted small mb-3">
                                Currency: {{ strtoupper($plan->currency) }}
                            </p>

                            <form method="POST"
                                  action="{{ route('tenant.plans.subscribe', ['tenant' => $tenantId, 'plan' => $plan->id]) }}"
                                  class="mt-auto">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100">
                                    Subscribe
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection