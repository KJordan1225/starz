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
                    <h3 class="card-title mb-3">Payment canceled</h3>
                    <p class="card-text">
                        Your payment for {{ $tenant->name }} was canceled or not completed.
                    </p>
                    <a href="{{ route('tenant.orders.create', ['tenant' => $tenantId]) }}"
                       class="btn btn-outline-secondary mt-3">
                        Try again
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection