@extends('layouts.landlord')

@section('content')
@php
    use App\Services\TenantService;
    $tenantService = new TenantService();
    $tenantId = $tenantService->getTenantId();
@endphp
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h3 class="card-title mb-3">Thank you!</h3>
                    <p class="card-text">
                        Your payment for {{ $tenant->name }} was received successfully.
                    </p>

                    @if($session_id)
                        <p class="text-muted small mb-0">
                            Checkout Session ID: <code>{{ $session_id }}</code>
                        </p>
                    @endif

                    <a href="{{ route('tenant.orders.create', ['tenant' => $tenantId]) }}"
                       class="btn btn-outline-primary mt-3">
                        Make another payment
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection