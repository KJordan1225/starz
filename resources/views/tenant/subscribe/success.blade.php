@extends('layouts.landlord')

@section('content')

@php
    use App\Services\TenantService;
    $tenantService = new TenantService();
    $tenantId = $tenantService->getTenantId();
@endphp


<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">

            <div class="card shadow-sm text-center">
                <div class="card-body p-4">
                    
                    <div class="mb-3">
                        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center"
                             style="width: 72px; height: 72px;">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 width="36" height="36"
                                 fill="currentColor"
                                 class="text-success"
                                 viewBox="0 0 16 16">
                                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0z"/>
                                <path d="M12.03 5.97a.75.75 0 0 0-1.08-1.04L7.477 8.417 5.384 6.323a.75.75 0 1 0-1.06 1.06l2.65 2.65a.75.75 0 0 0 1.08 0l4.976-4.976z"/>
                            </svg>
                        </div>
                    </div>

                    <h3 class="card-title mb-2">
                        Subscription Successful 🎉
                    </h3>

                    <p class="card-text mb-3">
                        You are now subscribed to
                        <strong>{{ $tenant->id }}</strong>.
                    </p>

                    <p class="text-muted small mb-4">
                        Your subscription is active and premium content is now unlocked.
                    </p>

                    @if(!empty($session_id))
                        <div class="alert alert-light border small text-start">
                            <strong>Checkout Session ID</strong><br>
                            <code class="text-muted">{{ $session_id }}</code>
                        </div>
                    @endif

                    <div class="d-grid gap-2 mt-4">
                        {{-- Primary next step --}}
                        <a href="{{ route('tenant.user.home', ['tenant' => $tenantId]) }}"
                           class="btn btn-primary">
                            Go to {{ $tenant->id }} dashboard
                        </a>                        
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection