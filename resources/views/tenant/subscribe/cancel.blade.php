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
                        <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center"
                             style="width: 72px; height: 72px;">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 width="36" height="36"
                                 fill="currentColor"
                                 class="text-warning"
                                 viewBox="0 0 16 16">
                                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.964 0L.165 13.233c-.457.778.091 1.767.982 1.767h13.706c.89 0 1.438-.99.982-1.767L8.982 1.566z"/>
                                <path d="M8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                            </svg>
                        </div>
                    </div>

                    <h3 class="card-title mb-2">
                        Subscription Not Completed
                    </h3>

                    <p class="card-text mb-3">
                        Your subscription to
                        <strong>{{ $tenant->id }}</strong>
                        was canceled before completion.
                    </p>

                    <p class="text-muted small mb-4">
                        No charges were made. You can try again at any time.
                    </p>

                    <div class="d-grid gap-2 mt-4">
                        {{-- Retry subscription --}}
                        <a href="{{ route('tenant.plans.index', ['tenant' => $tenant->id]) }}"
                           class="btn btn-primary">
                            View Subscription Plans
                        </a>

                        {{-- Back to tenant home --}}
                        <a href="{{ route('tenant.user.home', ['tenant' => $tenant->id]) }}"
                           class="btn btn-outline-secondary">
                            Return to {{ $tenant->id }} dashboard
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
